<?php

namespace app\controller\user;

use app\common\CurrentUser;
use app\service\quota\QuotaService;
use app\service\search\SearchLogService;
use app\service\search\SearchService;
use app\validate\SearchQueryValidator;
use support\ApiResponse;
use support\Request;

class SearchController
{
    public function query(Request $request)
    {
        $userId = CurrentUser::id($request);
        $payload = [
            'q' => (string) $request->input('q', ''),
            'info' => (string) $request->input('info', ''),
            'split' => (string) $request->input('split', '###'),
        ];

        [$passed, $message] = (new SearchQueryValidator())->validate($payload);
        if (!$passed) {
            return ApiResponse::error(40001, $message);
        }

        $quotaService = new QuotaService();
        $remainQuota = $quotaService->getUserQuota($userId);
        if ($remainQuota <= 0) {
            return ApiResponse::error(40006, '额度不足');
        }

        $searchService = new SearchService();
        $result = $searchService->query($payload['q'], $payload['info'], $payload['split']);
        $quotaService->consume($userId, 1);
        $log = (new SearchLogService())->create([
            'user_id' => $userId,
            'keyword' => $payload['q'],
            'request' => $payload,
            'result' => $result,
        ]);
        $result['log_no'] = $log['log_no'];

        return ApiResponse::success($result, 'success');
    }

    public function logs()
    {
        return ApiResponse::success([
            'list' => [],
            'total' => 0,
            'page' => 1,
            'page_size' => 20,
        ]);
    }
}
