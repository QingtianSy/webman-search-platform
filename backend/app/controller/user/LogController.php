<?php

namespace app\controller\user;

use app\common\CurrentUser;
use app\common\user\UserListBuilder;
use app\repository\mysql\BalanceLogRepository;
use app\repository\mysql\LoginLogRepository;
use app\repository\mysql\OperateLogRepository;
use app\repository\mysql\PaymentLogRepository;
use app\validate\user\LogQueryValidate;
use support\ApiResponse;
use support\Request;

class LogController
{
    public function balance(Request $request)
    {
        $query = (new LogQueryValidate())->list($request->all());
        $list = (new BalanceLogRepository())->listByUserId(CurrentUser::id($request));
        return ApiResponse::success(UserListBuilder::make($list, $query['page'], $query['page_size']));
    }

    public function payment(Request $request)
    {
        $query = (new LogQueryValidate())->list($request->all());
        $list = (new PaymentLogRepository())->listByUserId(CurrentUser::id($request));
        return ApiResponse::success(UserListBuilder::make($list, $query['page'], $query['page_size']));
    }

    public function login(Request $request)
    {
        $query = (new LogQueryValidate())->list($request->all());
        $list = (new LoginLogRepository())->listByUserId(CurrentUser::id($request));
        return ApiResponse::success(UserListBuilder::make($list, $query['page'], $query['page_size']));
    }

    public function operate(Request $request)
    {
        $query = (new LogQueryValidate())->list($request->all());
        $list = (new OperateLogRepository())->listByUserId(CurrentUser::id($request));
        return ApiResponse::success(UserListBuilder::make($list, $query['page'], $query['page_size']));
    }
}
