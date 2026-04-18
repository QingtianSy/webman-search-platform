<?php

namespace app\controller\user;

use app\common\CurrentUser;
use app\service\user\DashboardService;
use support\ApiResponse;
use support\Request;

class DashboardController
{
    public function overview(Request $request)
    {
        $userId = CurrentUser::id($request);
        return ApiResponse::success((new DashboardService())->overview($userId));
    }
}
