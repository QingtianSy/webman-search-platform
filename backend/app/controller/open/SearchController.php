<?php

namespace app\controller\open;

use app\service\open\ApiKeyService;
use app\service\quota\QuotaService;
use app\service\search\SearchService;
use support\ApiResponse;
use support\Request;

class SearchController
{
    public function query(Request $request)
    {
                $apiKey = (string) $request->header('x-api-key', '');
        $apiSecret = (string) $request->header('x-api-secret', '');
        if (!(new ApiKeyService())->verify($apiKey, $apiSecret)) {
            return ApiResponse::error(40008, 'API Key 无效');
        }

        $keyword = trim((string) $request->input('q', ''));
        $info = (string) $request->input('info', '');
        $split = (string) $request->input('split', '###');

        if ($keyword === '' || mb_strlen($keyword) < 2) {
            return ApiResponse::error(40001, '搜索关键词最少2个字符');
        }

        $searchService = new SearchService();
        $result = $searchService->query($keyword, $info, $split);

        return ApiResponse::success($result['list'][0]['answer_text'] ?? 'TODO_ANSWER', 'success');
    }

    public function quotaDetail()
    {
        return ApiResponse::success([
            'remain_quota' => (new QuotaService())->getUserQuota(1),
            'is_unlimited' => 0,
        ]);
    }
}
