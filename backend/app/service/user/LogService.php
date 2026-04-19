<?php

namespace app\service\user;

use app\repository\mysql\BalanceLogRepository;
use app\repository\mysql\LoginLogRepository;
use app\repository\mysql\OperateLogRepository;
use app\repository\mysql\PaymentLogRepository;

class LogService
{
    public function balance(int $userId, array $query = []): array
    {
        $query += ['page' => 1, 'page_size' => 20];
        return (new BalanceLogRepository())->listByUserId($userId, (int) $query['page'], (int) $query['page_size']);
    }

    public function payment(int $userId, array $query = []): array
    {
        $query += ['page' => 1, 'page_size' => 20];
        return (new PaymentLogRepository())->listByUserId($userId, (int) $query['page'], (int) $query['page_size']);
    }

    public function login(int $userId, array $query = []): array
    {
        $query += ['page' => 1, 'page_size' => 20];
        return (new LoginLogRepository())->listByUserId($userId, (int) $query['page'], (int) $query['page_size']);
    }

    public function operate(int $userId, array $query = []): array
    {
        $query += ['page' => 1, 'page_size' => 20];
        return (new OperateLogRepository())->listByUserId($userId, (int) $query['page'], (int) $query['page_size']);
    }
}
