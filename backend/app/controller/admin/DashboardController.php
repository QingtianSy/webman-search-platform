<?php

namespace app\controller\admin;

use app\service\admin\DashboardAdminService;
use support\ApiResponse;
use support\Request;

class DashboardController
{
    public function overview(Request $request)
    {
        return ApiResponse::success((new DashboardAdminService())->overview());
    }
}
