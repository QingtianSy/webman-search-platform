<?php

namespace app\service\user;

use app\exception\BusinessException;
use app\repository\mysql\BalanceLogRepository;
use app\repository\mysql\LoginLogRepository;
use app\repository\mysql\OperateLogRepository;
use app\repository\mysql\PaymentLogRepository;

class LogService
{
    // 用户日志页面（余额/支付/登录/操作）在 DB 故障时之前都返回"空列表 200"。
    // 用户看到"你没有流水/没有登录记录"——其实是后端挂了，既掩盖故障又可能诱导误操作。
    // 统一改为 DB 故障抛 50001，由前端显示错误横幅。

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
            return (new PaymentLogRepository())->listByUserId($userId, (int) $query['page'], (int) $query['page_size']);
        } catch (\Throwable $e) {
            error_log("[LogService] payment failed: " . $e->getMessage());
            throw new BusinessException('支付流水暂不可用，请稍后重试', 50001);
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
