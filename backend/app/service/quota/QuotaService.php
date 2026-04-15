<?php

namespace app\service\quota;

class QuotaService
{
    public function getUserQuota(int $userId): int
    {
        return 1000;
    }

    public function consume(int $userId, int $amount = 1): bool
    {
        return $amount > 0;
    }
}
