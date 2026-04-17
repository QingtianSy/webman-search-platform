<?php

namespace app\service\user;

use app\repository\mysql\SubscriptionRepository;
use app\repository\mysql\WalletRepository;

class BillingService
{
    public function wallet(int $userId): array
    {
        return (new WalletRepository())->findByUserId($userId);
    }

    public function currentPlan(int $userId): array
    {
        return (new SubscriptionRepository())->findCurrentByUserId($userId);
    }
}
