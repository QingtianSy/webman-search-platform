<?php

namespace app\service\search;

use app\exception\BusinessException;
use app\repository\es\QuestionIndexRepository;
use app\repository\mongo\QuestionRepository;
use app\repository\mongo\SearchLogDetailRepository;
use app\repository\mysql\SearchLogRepository;
use app\service\log\LogService;
use app\service\question\QuestionService;
use app\service\quota\QuotaService;
use app\service\search\ThirdPartySearchService;
use Workerman\Timer;

class SearchService
{
    public function query(int $userId, string $keyword, string $info = '', string $split = '###', ?int $apiKeyId = null): array
    {
        $logService = new LogService();
        $startMs = (int) (microtime(true) * 1000);
        $logNo = 'SL' . date('YmdHis') . mt_rand(1000, 9999);

        $quotaService = null;
        if ($userId > 0) {
            $quotaService = new QuotaService();
            if ($quotaService->getUserQuota($userId) <= 0) {
                throw new BusinessException('额度不足', 40006);
            }
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

        $esHits = (new QuestionIndexRepository())->search($keyword);
        $searchSource = 'es';
        if (empty($esHits) && trim($keyword) !== '') {
            $mongoResults = (new QuestionRepository())->search($keyword);
            $esHits = array_map(fn($r) => [
                'question_id' => $r['question_id'] ?? '',
                'score' => $r['score'] ?? 100,
            ], array_slice($mongoResults, 0, 20));
            if (!empty($esHits)) {
                $searchSource = 'mongo';
            }
        }
        $questionIds = array_map(fn ($row) => (string) ($row['question_id'] ?? ''), $esHits);
        $questionIds = array_values(array_filter($questionIds));
        $scoreMap = [];
        foreach ($esHits as $hit) {
            $scoreMap[(string) ($hit['question_id'] ?? '')] = $hit['score'] ?? null;
        }
        $list = (new QuestionService())->findManyByIds($questionIds);
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
                error_log("[SearchService] third-party search failed: " . $e->getMessage());
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

        $consumeQuota = 0;
        if ($totalHitCount > 0 && $userId > 0 && $quotaService !== null) {
            if (!$quotaService->consume($userId, 1)) {
                $this->writeSearchLog($logNo, $userId, $apiKeyId, $keyword, $totalHitCount, $searchSource, $hasApiHits, 0, $costMs, $questionIds, $apiHitCount);
                throw new BusinessException('额度不足', 40006);
            }
            $consumeQuota = 1;
        }

        $this->writeSearchLog($logNo, $userId, $apiKeyId, $keyword, $totalHitCount, $searchSource, $hasApiHits, $consumeQuota, $costMs, $questionIds, $apiHitCount);

        $safeApiResults = array_map(fn($r) => [
            'source_id' => $r['source_id'] ?? null,
            'source_name' => $r['source_name'] ?? '',
            'status' => $r['status'] ?? 'error',
            'data' => $r['data'] ?? [],
        ], $apiResults);

        return [
            'log_no' => $logNo,
            'hit_count' => $totalHitCount,
            'consume_quota' => $consumeQuota,
            'list' => $list,
            'api_results' => $safeApiResults,
            'keyword' => $keyword,
            'info' => $info,
            'split' => $split,
        ];
    }

    protected function writeSearchLog(string $logNo, int $userId, ?int $apiKeyId, string $keyword, int $totalHitCount, string $searchSource, bool $hasApiHits, int $consumeQuota, int $costMs, array $questionIds, int $apiHitCount): void
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
        $detailData = [
            'log_no' => $logNo,
            'keyword' => $keyword,
            'hit_count' => $totalHitCount,
            'question_ids' => $questionIds,
            'api_hit_count' => $apiHitCount,
            'cost_ms' => $costMs,
        ];
        Timer::add(0.001, function () use ($logData, $detailData) {
            try {
                (new SearchLogRepository())->create($logData);
                (new SearchLogDetailRepository())->create($detailData);
            } catch (\Throwable $e) {
                error_log("[SearchService] deferred log write failed: " . $e->getMessage());
            }
        }, [], false);
    }
}
