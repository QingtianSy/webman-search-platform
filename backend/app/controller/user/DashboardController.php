<?php

namespace app\controller\user;

use app\service\user\DashboardService;
use support\ApiResponse;
use support\Request;

class DashboardController
{
    public function overview(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        return ApiResponse::success((new DashboardService())->overview($userId));
    }
}
