<?php

namespace app\service\user;

use app\common\user\UserListBuilder;
use app\repository\mysql\BalanceLogRepository;
use app\repository\mysql\LoginLogRepository;
use app\repository\mysql\OperateLogRepository;
use app\repository\mysql\PaymentLogRepository;

class LogService
{
    public function balance(int $userId, array $query = []): array
    {
        $query += ['page' => 1, 'page_size' => 20];
        $list = (new BalanceLogRepository())->listByUserId($userId);
        return UserListBuilder::make($list, (int) $query['page'], (int) $query['page_size']);
    }

    public function payment(int $userId, array $query = []): array
    {
        $query += ['page' => 1, 'page_size' => 20];
        $list = (new PaymentLogRepository())->listByUserId($userId);
        return UserListBuilder::make($list, (int) $query['page'], (int) $query['page_size']);
    }

    public function login(int $userId, array $query = []): array
    {
        $query += ['page' => 1, 'page_size' => 20];
        $list = (new LoginLogRepository())->listByUserId($userId);
        return UserListBuilder::make($list, (int) $query['page'], (int) $query['page_size']);
    }

    public function operate(int $userId, array $query = []): array
    {
        $query += ['page' => 1, 'page_size' => 20];
        $list = (new OperateLogRepository())->listByUserId($userId);
        return UserListBuilder::make($list, (int) $query['page'], (int) $query['page_size']);
    }
}
