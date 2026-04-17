<?php

namespace app\controller\user;

use app\repository\mysql\BalanceLogRepository;
use app\repository\mysql\LoginLogRepository;
use app\repository\mysql\OperateLogRepository;
use app\repository\mysql\PaymentLogRepository;
use support\ApiResponse;
use support\Pagination;

class LogController
{
    public function balance()
    {
        $list = (new BalanceLogRepository())->listByUserId(1);
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }

    public function payment()
    {
        $list = (new PaymentLogRepository())->listByUserId(1);
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }

    public function login()
    {
        $list = (new LoginLogRepository())->listByUserId(1);
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }

    public function operate()
    {
        $list = (new OperateLogRepository())->listByUserId(1);
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }
}
