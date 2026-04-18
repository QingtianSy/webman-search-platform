<?php

namespace app\service\quota;

class QuotaService
{
    public function getUserQuota(int $userId): int
    {
        if (config('integration.log_source', 'mock') === 'real') {
            return $this->getUserQuotaReal($userId);
        }
        return 1000;
    }

    public function consume(int $userId, int $amount = 1): bool
    {
        if (config('integration.log_source', 'mock') === 'real') {
            return $this->consumeReal($userId, $amount);
        }
        return $amount > 0;
    }

    protected function getUserQuotaReal(int $userId): int
    {
        /**
         * 未来真实实现说明：
         * - 优先从 Redis quota:user:{id} 读取
         * - 没有则从 MySQL user_subscriptions / quota_logs 聚合
         */
        return 0;
    }

    protected function consumeReal(int $userId, int $amount): bool
    {
        /**
         * 未来真实实现说明：
         * - Redis 原子扣减
         * - 失败回退/拒绝
         * - 同步写 quota_logs
         */
        return $amount > 0;
    }
}
