<?php

namespace app\controller\user;

use app\common\CurrentUser;
use app\service\user\BillingService;
use support\ApiResponse;
use support\Request;

class BillingController
{
    public function wallet(Request $request)
    {
        $userId = CurrentUser::id($request);
        return ApiResponse::success((new BillingService())->wallet($userId));
    }

    public function currentPlan(Request $request)
    {
        $userId = CurrentUser::id($request);
        return ApiResponse::success((new BillingService())->currentPlan($userId));
    }
}
