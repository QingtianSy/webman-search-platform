<?php

namespace app\controller\user;

use app\common\CurrentUser;
use app\common\user\UserListBuilder;
use app\common\user\UserQuery;
use app\service\user\CollectService;
use app\validate\user\CollectValidate;
use support\ApiResponse;
use support\Request;

class CollectController
{
    public function accounts(Request $request)
    {
        $userId = CurrentUser::id($request);
        $query = UserQuery::parse($request->get());
        $list = (new CollectService())->accounts($userId);
        return ApiResponse::success(UserListBuilder::make($list, $query['page'], $query['page_size']));
    }

    public function tasks(Request $request)
    {
        $userId = CurrentUser::id($request);
        $query = UserQuery::parse($request->get());
        $list = (new CollectService())->tasks($userId);
        return ApiResponse::success(UserListBuilder::make($list, $query['page'], $query['page_size']));
    }

    public function detail(Request $request)
    {
        $taskNo = (new CollectValidate())->taskNo($request->get());
        return ApiResponse::success((new CollectService())->detail($taskNo));
    }
}
