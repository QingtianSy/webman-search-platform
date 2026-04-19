<?php

namespace app\service\search;

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

        $logService->info('search.query', [
            'keyword' => $keyword,
            'log_no' => $logNo,
            'user_id' => $userId,
        ]);

        $esHits = (new QuestionIndexRepository())->search($keyword);
        $questionIds = array_map(fn ($row) => (int) ($row['question_id'] ?? 0), $esHits);
        $questionIds = array_values(array_filter($questionIds));
        $list = (new QuestionService())->findManyByIds($questionIds);
        $hitCount = count($list);
        $costMs = (int) (microtime(true) * 1000) - $startMs;
        $consumeQuota = $hitCount > 0 ? 1 : 0;

        if ($consumeQuota > 0 && $userId > 0) {
            (new QuotaService())->consume($userId, $consumeQuota);
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
