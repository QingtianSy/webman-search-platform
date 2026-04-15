<?php

namespace app\service\quota;

class QuotaService
{
    public function getUserQuota(int $userId): int
    {
        return 0;
    }

    public function consume(int $userId, int $amount = 1): bool
    {
        return true;
    }
}
