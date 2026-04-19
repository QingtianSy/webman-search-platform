<?php

namespace app\controller\user;

use app\common\CurrentUser;
use app\common\user\UserQuery;
use app\service\quota\QuotaService;
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

        $remainQuota = (new QuotaService())->getUserQuota($userId);
        if ($remainQuota <= 0) {
            return ApiResponse::error(40006, '额度不足');
        }

        $result = (new SearchService())->query($userId, $payload['q'], $payload['info'], $payload['split']);

        return ApiResponse::success($result, 'success');
    }

    public function logs(Request $request)
    {
        $userId = CurrentUser::id($request);
        $query = UserQuery::parse($request->get());
        return ApiResponse::success((new SearchHistoryService())->getList($userId, $query));
    }
}
