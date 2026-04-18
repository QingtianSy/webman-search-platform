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

        return ApiResponse::success($result['list'][0]['answer_text'] ?? 'TODO_ANSWER', 'success');
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
