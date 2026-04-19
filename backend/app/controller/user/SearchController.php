<?php

namespace app\controller\user;

use app\common\user\UserQuery;
use app\service\search\SearchService;
use app\service\user\SearchHistoryService;
use app\validate\user\SearchValidate;
use support\ApiResponse;
use support\Request;

class SearchController
{
    public function query(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $payload = (new SearchValidate())->query($request->post());

        $result = (new SearchService())->query($userId, $payload['q'], $payload['info'], $payload['split']);

        return ApiResponse::success($result, 'success');
    }

    public function logs(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $query = UserQuery::parse($request->get());
        return ApiResponse::success((new SearchHistoryService())->getList($userId, $query));
    }
}
