<?php

namespace app\controller\user;

use app\service\user\BillingService;
use support\ApiResponse;
use support\Request;

class BillingController
{
    public function wallet(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        return ApiResponse::success((new BillingService())->wallet($userId));
    }

    public function currentPlan(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        return ApiResponse::success((new BillingService())->currentPlan($userId));
    }
}
