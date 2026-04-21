<?php

namespace app\service\search;

use app\exception\BusinessException;
use app\repository\es\QuestionIndexRepository;
use app\repository\mongo\SearchLogDetailRepository;
use app\repository\mysql\SearchLogRepository;
use app\service\log\LogService;
use app\service\question\QuestionService;
use app\service\quota\QuotaService;
use app\service\search\ThirdPartySearchService;
use support\AppLog;
use Workerman\Timer;

class SearchService
{
    public function query(int $userId, string $keyword, string $info = '', string $split = '###', ?int $apiKeyId = null): array
    {
        $logService = new LogService();
        $startMs = (int) (microtime(true) * 1000);
        $logNo = 'SL' . date('YmdHis') . bin2hex(random_bytes(6));

        $quotaService = null;
        $reservedQuota = 0;
        if ($userId > 0) {
            $quotaService = new QuotaService();
            // 快速路径：缓存命中额度为 0 时直接拒绝，避免后续 DB 往返。
            if ($quotaService->getUserQuota($userId) <= 0) {
                throw new BusinessException('额度不足', 40006);
            }
            // 在调用第三方之前原子预扣一次额度，防止并发请求都通过预检后同时打第三方。
            // 命中为 0 时再 refund，保持"按命中计费"语义。
            if (!$quotaService->consume($userId, 1)) {
                throw new BusinessException('额度不足', 40006);
            }
            $reservedQuota = 1;
        }

        $logService->info('search.query', [
            'keyword' => $keyword,
            'log_no' => $logNo,
            'user_id' => $userId,
        ]);

        $apiResultsFn = null;
        if ($userId > 0) {
            $apiResultsFn = (new ThirdPartySearchService())->startQuery($userId, $keyword, $info, $split);
        }

        // ES/Mongo 检索走严格路径：连接失败/查询异常直接抛出，由下面统一退预扣 + 50001。
        // 避免"ES 挂掉 → 空列表 200"式的故障伪装。ES 未配置（空数组，非异常）仍视为未启用，走 Mongo 正则兜底。
        $searchSource = 'es';
        try {
            $esHits = (new QuestionIndexRepository())->searchStrict($keyword);
            if (empty($esHits) && trim($keyword) !== '') {
                $mongoResults = (new QuestionService())->searchMongoStrict($keyword);
                $esHits = array_map(fn($r) => [
                    'question_id' => $r['question_id'] ?? '',
                    'score' => $r['score'] ?? 100,
                ], array_slice($mongoResults, 0, 20));
                if (!empty($esHits)) {
                    $searchSource = 'mongo';
                }
            }
        } catch (\Throwable $e) {
            if ($reservedQuota > 0 && $quotaService !== null) {
                $quotaService->refund($userId, $reservedQuota);
            }
            AppLog::warn("[SearchService] index search failed: " . $e->getMessage());
            throw new BusinessException('搜索服务暂不可用，请稍后重试', 50001);
        }
        $questionIds = array_map(fn ($row) => (string) ($row['question_id'] ?? ''), $esHits);
        $questionIds = array_values(array_filter($questionIds));
        $scoreMap = [];
        foreach ($esHits as $hit) {
            $scoreMap[(string) ($hit['question_id'] ?? '')] = $hit['score'] ?? null;
        }
        // 关键一致性：ES 已返回 question_id 就必须能拿到详情，否则把"详情取不到"暴露为 50001，
        // 不要伪装成"没搜到"。空 ids → 直接空结果，不查 Mongo。
        try {
            $list = (new QuestionService())->findManyByIdsStrict($questionIds);
        } catch (\Throwable $e) {
            if ($reservedQuota > 0 && $quotaService !== null) {
                $quotaService->refund($userId, $reservedQuota);
            }
            AppLog::warn("[SearchService] fetch question details failed: " . $e->getMessage());
            throw new BusinessException('题库数据源暂不可用，请稍后重试', 50001);
        }
        // 脏索引校验：ES 命中的 question_id 必须在 Mongo 里有对应详情。
        // 有命中但详情缺失 = 索引漂移（删除未同步、reindex 中途），此时返回部分结果等于把脏数据当作正确命中。
        // 明确作为 50001 暴露出去，让运维知道要跑 reindex，而不是静默给用户少几条结果。
        if (!empty($questionIds) && count($list) < count($questionIds)) {
            if ($reservedQuota > 0 && $quotaService !== null) {
                $quotaService->refund($userId, $reservedQuota);
            }
            $got = array_map(fn($r) => (string)($r['question_id'] ?? ''), $list);
            $missing = array_values(array_diff($questionIds, $got));
            AppLog::warn('[SearchService] dirty index: ' . count($missing) . '/' . count($questionIds)
                . ' missing detail, sample=' . implode(',', array_slice($missing, 0, 3)));
            throw new BusinessException('搜索索引与题库不一致，请稍后重试', 50001);
        }
        foreach ($list as &$item) {
            $qid = (string) ($item['question_id'] ?? '');
            $item['score'] = $scoreMap[$qid] ?? null;
        }
        unset($item);
        $hitCount = count($list);
        $costMs = (int) (microtime(true) * 1000) - $startMs;

        $apiResults = [];
        if ($apiResultsFn !== null) {
            try {
                $apiResults = $apiResultsFn();
            } catch (\Throwable $e) {
                AppLog::warn("[SearchService] third-party search failed: " . $e->getMessage());
            }
        }

        $apiHitCount = 0;
        foreach ($apiResults as $ar) {
            if (($ar['status'] ?? '') === 'success' && !empty($ar['data'])) {
                $apiHitCount += is_array($ar['data']) ? count($ar['data']) : 1;
            }
        }
        $hasApiHits = $apiHitCount > 0;
        $totalHitCount = $hitCount + $apiHitCount;

        $consumeQuota = $reservedQuota;
        if ($reservedQuota > 0 && $totalHitCount === 0 && $quotaService !== null) {
            // 零命中 → 退回预扣的额度，保持"按命中计费"。退款失败仅记录日志，不影响响应。
            if ($quotaService->refund($userId, 1)) {
                $consumeQuota = 0;
            } else {
                AppLog::warn("[SearchService] refund failed after no-hit search: user={$userId} log_no={$logNo}");
            }
        }

        // 主日志改为同步写，保证响应里的 log_no 一定可查：
        //   - 撞键（12 位 hex 概率极低但非零）→ 重新生成 log_no 再试一次
        //   - 重试仍失败（持续撞键/DB 不可用）→ log_no 置空返回，前端知道"此次无可查询日志号"
        // 明细（Mongo）仍走 Timer 异步，不阻塞响应。
        $persistedLogNo = $this->writeSearchLog($logNo, $userId, $apiKeyId, $keyword, $totalHitCount, $searchSource, $hasApiHits, $consumeQuota, $costMs, $questionIds, $apiHitCount);

        $safeApiResults = array_map(fn($r) => [
            'source_id' => $r['source_id'] ?? null,
            'source_name' => $r['source_name'] ?? '',
            'status' => $r['status'] ?? 'error',
            'data' => $r['data'] ?? [],
        ], $apiResults);

        return [
            'log_no' => $persistedLogNo,
            'hit_count' => $totalHitCount,
            'consume_quota' => $consumeQuota,
            'list' => $list,
            'api_results' => $safeApiResults,
            'keyword' => $keyword,
            'info' => $info,
            'split' => $split,
        ];
    }

    protected function writeSearchLog(string $logNo, int $userId, ?int $apiKeyId, string $keyword, int $totalHitCount, string $searchSource, bool $hasApiHits, int $consumeQuota, int $costMs, array $questionIds, int $apiHitCount): string
    {
        $sourceType = $totalHitCount > 0 && $hasApiHits
            ? ($totalHitCount - $apiHitCount > 0 ? $searchSource . '+api' : 'api')
            : $searchSource;
        $logData = [
            'log_no' => $logNo,
            'user_id' => $userId ?: null,
            'api_key_id' => $apiKeyId,
            'keyword' => $keyword,
            'question_type' => null,
            'status' => $totalHitCount > 0 ? 1 : 0,
            'hit_count' => $totalHitCount,
            'source_type' => $sourceType,
            'consume_quota' => $consumeQuota,
            'cost_ms' => $costMs,
        ];

        // 同步写主日志：撞键重试 1 次，避免把响应里的 log_no 钉死在不可查的号上。
        $repo = new SearchLogRepository();
        $result = $repo->create($logData);
        if ($result === 'duplicate') {
            $retryNo = 'SL' . date('YmdHis') . bin2hex(random_bytes(6));
            $logData['log_no'] = $retryNo;
            $result = $repo->create($logData);
            if ($result === 'ok') {
                AppLog::warn("[SearchService] log_no collision resolved by retry: original={$logNo} retry={$retryNo}");
                $logNo = $retryNo;
            } else {
                AppLog::warn("[SearchService] log_no collision unresolved after retry: original={$logNo} retry={$retryNo} result={$result}");
                return '';
            }
        } elseif ($result !== 'ok') {
            AppLog::warn("[SearchService] main search log write failed ({$result}) log_no={$logNo}");
            return '';
        }

        $detailData = [
            'log_no' => $logNo,
            'keyword' => $keyword,
            'hit_count' => $totalHitCount,
            'question_ids' => $questionIds,
            'api_hit_count' => $apiHitCount,
            'cost_ms' => $costMs,
        ];
        // 明细写异步：Mongo 偶发失败不影响主日志与响应 log_no 的一致性。
        Timer::add(0.001, function () use ($detailData) {
            try {
                (new SearchLogDetailRepository())->create($detailData);
            } catch (\Throwable $e) {
                AppLog::warn("[SearchService] deferred detail write failed: " . $e->getMessage());
            }
        }, [], false);

        return $logNo;
    }
}
