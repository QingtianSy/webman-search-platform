<?php

namespace app\controller\user;

use app\common\CurrentUser;
use app\service\user\LogService;
use app\validate\user\LogQueryValidate;
use support\ApiResponse;
use support\Request;

class LogController
{
    public function balance(Request $request)
    {
        $query = (new LogQueryValidate())->list($request->get());
        return ApiResponse::success((new LogService())->balance(CurrentUser::id($request), $query));
    }

    public function payment(Request $request)
    {
        $query = (new LogQueryValidate())->list($request->get());
        return ApiResponse::success((new LogService())->payment(CurrentUser::id($request), $query));
    }

    public function login(Request $request)
    {
        $query = (new LogQueryValidate())->list($request->get());
        return ApiResponse::success((new LogService())->login(CurrentUser::id($request), $query));
    }

    public function operate(Request $request)
    {
        $query = (new LogQueryValidate())->list($request->get());
        return ApiResponse::success((new LogService())->operate(CurrentUser::id($request), $query));
    }
}
