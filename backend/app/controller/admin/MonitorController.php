<?php

namespace app\controller\admin;

use app\service\admin\MonitorService;
use support\ApiResponse;
use support\Request;

class MonitorController
{
    public function overview(Request $request)
    {
        return ApiResponse::success((new MonitorService())->overview());
    }
}
