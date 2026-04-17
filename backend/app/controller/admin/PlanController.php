<?php

namespace app\controller\admin;

use app\service\admin\PlanAdminService;
use support\ApiResponse;

class PlanController
{
    public function index()
    {
        return ApiResponse::success((new PlanAdminService())->getList());
    }
}
