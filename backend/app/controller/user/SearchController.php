<?php

namespace app\controller\user;

use app\service\quota\QuotaService;
use app\service\search\SearchService;
use support\ApiResponse;
use support\Request;

class SearchController
{
    public function query(?Request $request = null): array
    {
        $request ??= new Request();
        $keyword = trim((string) $request->input('q', ''));
        $info = (string) $request->input('info', '');
        $split = (string) $request->input('split', '###');

        if ($keyword === '' || mb_strlen($keyword) < 2) {
            return ApiResponse::error(40001, '搜索关键词最少2个字符');
        }

        $quotaService = new QuotaService();
        $remainQuota = $quotaService->getUserQuota(1);
        if ($remainQuota <= 0) {
            return ApiResponse::error(40006, '额度不足');
        }

        $searchService = new SearchService();
        $result = $searchService->query($keyword, $info, $split);
        $quotaService->consume(1, 1);

        return ApiResponse::success($result, 'success');
    }

    public function logs(): array
    {
        return ApiResponse::success([
            'list' => [],
            'total' => 0,
            'page' => 1,
            'page_size' => 20,
        ]);
    }
}
