<?php

namespace app\controller\user;

use app\common\CurrentUser;
use app\common\user\UserListBuilder;
use app\common\user\UserQuery;
use app\repository\mysql\CollectAccountRepository;
use app\repository\mysql\CollectTaskDetailRepository;
use app\repository\mysql\CollectTaskRepository;
use app\validate\user\CollectValidate;
use support\ApiResponse;
use support\Request;

class CollectController
{
    public function accounts(Request $request)
    {
        $userId = CurrentUser::id($request);
        $query = UserQuery::parse($request->all());
        $list = (new CollectAccountRepository())->listByUserId($userId);
        return ApiResponse::success(UserListBuilder::make($list, $query['page'], $query['page_size']));
    }

    public function tasks(Request $request)
    {
        $userId = CurrentUser::id($request);
        $query = UserQuery::parse($request->all());
        $list = (new CollectTaskRepository())->listByUserId($userId);
        return ApiResponse::success(UserListBuilder::make($list, $query['page'], $query['page_size']));
    }

    public function detail(Request $request)
    {
        $taskNo = (new CollectValidate())->taskNo($request->all());
        return ApiResponse::success((new CollectTaskDetailRepository())->findByTaskNo($taskNo));
    }
}
