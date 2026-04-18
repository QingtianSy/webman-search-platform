<?php

namespace app\controller\open;

use app\repository\mysql\ApiKeyRepository;
use app\service\quota\QuotaService;
use app\service\search\SearchService;
use support\ApiResponse;
use support\Request;

class SearchController
{
    public function query(Request $request)
    {
        $keyword = trim((string) $request->post('q', ''));
        $info = (string) $request->post('info', '');
        $split = (string) $request->post('split', '###');

        if ($keyword === '' || mb_strlen($keyword) < 2) {
            return ApiResponse::error(40001, '搜索关键词最少2个字符');
        }

        $searchService = new SearchService();
        $result = $searchService->query($keyword, $info, $split);

        if (empty($result['list'])) {
            return ApiResponse::error(40004, '未找到匹配结果');
        }

        return ApiResponse::success([
            'log_no' => $result['log_no'] ?? '',
            'hit_count' => $result['hit_count'] ?? 0,
            'consume_quota' => $result['consume_quota'] ?? 0,
            'list' => array_map(fn ($item) => [
                'question_id' => $item['question_id'] ?? null,
                'stem' => $item['stem'] ?? '',
                'answer_text' => $item['answer_text'] ?? '',
                'type_name' => $item['type_name'] ?? '',
                'score' => $item['score'] ?? null,
            ], $result['list']),
        ]);
    }

    public function quotaDetail(Request $request)
    {
        $userId = $this->resolveUserId($request);
        return ApiResponse::success([
            'remain_quota' => (new QuotaService())->getUserQuota($userId),
            'is_unlimited' => 0,
        ]);
    }

    protected function resolveUserId(Request $request): int
    {
        $apiKey = (string) $request->header('x-api-key', '');
        if ($apiKey !== '') {
            $keyInfo = (new ApiKeyRepository())->findByApiKey($apiKey);
            if (!empty($keyInfo['user_id'])) {
                return (int) $keyInfo['user_id'];
            }
        }
        return 0;
    }
}
