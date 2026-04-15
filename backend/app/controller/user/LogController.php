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
    public function balance(): array
    {
        $list = (new BalanceLogRepository())->listByUserId(1);
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }

    public function payment(): array
    {
        $list = (new PaymentLogRepository())->listByUserId(1);
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }

    public function login(): array
    {
        $list = (new LoginLogRepository())->listByUserId(1);
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }

    public function operate(): array
    {
        $list = (new OperateLogRepository())->listByUserId(1);
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }
}
