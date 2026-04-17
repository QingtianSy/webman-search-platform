<?php

namespace app\controller\user;

use app\service\user\DashboardService;
use support\ApiResponse;

class DashboardController
{
    public function overview()
    {
        $service = new DashboardService();
        return ApiResponse::success($service->overview(1));
    }
}
