<?php

namespace app\controller\user;

use app\common\CurrentUser;
use app\repository\mysql\SubscriptionRepository;
use app\repository\mysql\WalletRepository;
use support\ApiResponse;
use support\Request;

class BillingController
{
    public function wallet(Request $request)
    {
        $userId = CurrentUser::id($request);
        return ApiResponse::success((new WalletRepository())->findByUserId($userId));
    }

    public function currentPlan(Request $request)
    {
        $userId = CurrentUser::id($request);
        return ApiResponse::success((new SubscriptionRepository())->findCurrentByUserId($userId));
    }
}
