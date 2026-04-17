<?php

namespace app\controller\user;

use app\common\CurrentUser;
use app\common\user\UserQuery;
use app\service\quota\QuotaService;
use app\service\search\SearchLogService;
use app\service\search\SearchService;
use app\service\user\SearchHistoryService;
use app\validate\user\SearchValidate;
use support\ApiResponse;
use support\Request;

class SearchController
{
    public function query(Request $request)
    {
        $userId = CurrentUser::id($request);
        $payload = (new SearchValidate())->query($request->post());

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

    public function logs(Request $request)
    {
        $userId = CurrentUser::id($request);
        $query = UserQuery::parse($request->get());
        return ApiResponse::success((new SearchHistoryService())->getList($userId, $query));
    }
}
