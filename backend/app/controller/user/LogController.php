<?php

namespace app\controller\user;

use app\service\user\LogService;
use app\validate\user\LogQueryValidate;
use support\ApiResponse;
use support\Request;

class LogController
{
    public function balance(Request $request)
    {
        $query = (new LogQueryValidate())->list($request->get());
        return ApiResponse::success((new LogService())->balance((int) ($request->userId ?? 0), $query));
    }

    public function payment(Request $request)
    {
        $query = (new LogQueryValidate())->list($request->get());
        return ApiResponse::success((new LogService())->payment((int) ($request->userId ?? 0), $query));
    }

    public function login(Request $request)
    {
        $query = (new LogQueryValidate())->list($request->get());
        return ApiResponse::success((new LogService())->login((int) ($request->userId ?? 0), $query));
    }

    public function operate(Request $request)
    {
        $query = (new LogQueryValidate())->list($request->get());
        return ApiResponse::success((new LogService())->operate((int) ($request->userId ?? 0), $query));
    }
}
