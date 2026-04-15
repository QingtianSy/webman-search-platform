<?php

namespace app\controller\user;

use app\repository\mysql\SubscriptionRepository;
use app\repository\mysql\WalletRepository;
use support\ApiResponse;

class BillingController
{
    public function wallet(): array
    {
        return ApiResponse::success((new WalletRepository())->findByUserId(1));
    }

    public function currentPlan(): array
    {
        return ApiResponse::success((new SubscriptionRepository())->findCurrentByUserId(1));
    }
}
