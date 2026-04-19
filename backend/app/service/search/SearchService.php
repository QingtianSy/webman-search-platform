<?php

namespace app\service\search;

use app\exception\BusinessException;
use app\repository\es\QuestionIndexRepository;
use app\repository\mongo\SearchLogDetailRepository;
use app\repository\mysql\SearchLogRepository;
use app\service\log\LogService;
use app\service\question\QuestionService;
use app\service\quota\QuotaService;

class SearchService
{
    public function query(int $userId, string $keyword, string $info = '', string $split = '###', ?int $apiKeyId = null): array
    {
        $logService = new LogService();
        $startMs = (int) (microtime(true) * 1000);
        $logNo = 'SL' . date('YmdHis') . mt_rand(1000, 9999);

        if ($userId > 0) {
            $quotaService = new QuotaService();
            if (!$quotaService->consume($userId, 1)) {
                throw new BusinessException('额度不足', 40006);
            }
        }

        $logService->info('search.query', [
            'keyword' => $keyword,
            'log_no' => $logNo,
            'user_id' => $userId,
        ]);

        $esHits = (new QuestionIndexRepository())->search($keyword);
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
        $consumeQuota = ($hitCount > 0 && $userId > 0) ? 1 : 0;

        if ($hitCount === 0 && $userId > 0 && isset($quotaService)) {
            if (!$quotaService->refund($userId, 1)) {
                error_log("[SearchService] WARN: refund failed for user={$userId} log_no={$logNo}, quota may be incorrectly deducted");
                $consumeQuota = 1;
            }
        }

        (new SearchLogRepository())->create([
            'log_no' => $logNo,
            'user_id' => $userId ?: null,
            'api_key_id' => $apiKeyId,
            'keyword' => $keyword,
            'question_type' => null,
            'status' => 1,
            'hit_count' => $hitCount,
            'source_type' => 'es',
            'consume_quota' => $consumeQuota,
            'cost_ms' => $costMs,
        ]);

        (new SearchLogDetailRepository())->create([
            'log_no' => $logNo,
            'keyword' => $keyword,
            'hit_count' => $hitCount,
            'question_ids' => $questionIds,
            'cost_ms' => $costMs,
        ]);

        return [
            'log_no' => $logNo,
            'hit_count' => $hitCount,
            'consume_quota' => $consumeQuota,
            'list' => $list,
            'keyword' => $keyword,
            'info' => $info,
            'split' => $split,
        ];
    }
}
