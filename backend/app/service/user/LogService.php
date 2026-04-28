<?php

namespace app\service\user;

use app\exception\BusinessException;
use app\repository\mysql\BalanceLogRepository;
use app\repository\mysql\LoginLogRepository;
use app\repository\mysql\OperateLogRepository;
use app\service\payment\OrderService as PaymentOrderService;

class LogService
{
    public function balance(int $userId, array $query = []): array
    {
        $query += ['page' => 1, 'page_size' => 20];
        try {
            return (new BalanceLogRepository())->listByUserIdStrict($userId, (int) $query['page'], (int) $query['page_size']);
        } catch (\RuntimeException $e) {
            throw new BusinessException('余额流水暂不可用，请稍后重试', 50001);
        }
    }

    public function payment(int $userId, array $query = []): array
    {
        $query += ['page' => 1, 'page_size' => 20];
        try {
            return (new PaymentOrderService())->listByUserId($userId, $query);
        } catch (\Throwable $e) {
            error_log('[LogService] payment failed: ' . $e->getMessage());
            throw new BusinessException('订单记录暂不可用，请稍后重试', 50001);
        }
    }

    public function login(int $userId, array $query = []): array
    {
        $query += ['page' => 1, 'page_size' => 20];
        try {
            return (new LoginLogRepository())->listByUserIdStrict($userId, (int) $query['page'], (int) $query['page_size']);
        } catch (\RuntimeException $e) {
            throw new BusinessException('登录记录暂不可用，请稍后重试', 50001);
        }
    }

    public function operate(int $userId, array $query = []): array
    {
        $query += ['page' => 1, 'page_size' => 20];
        try {
            return (new OperateLogRepository())->listByUserIdStrict($userId, (int) $query['page'], (int) $query['page_size']);
        } catch (\RuntimeException $e) {
            throw new BusinessException('操作记录暂不可用，请稍后重试', 50001);
        }
    }
}
