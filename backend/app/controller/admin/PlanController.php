<?php

namespace app\controller\admin;

use app\common\admin\AdminQuery;
use app\service\admin\PlanAdminService;
use support\ApiResponse;
use support\Request;

class PlanController
{
    public function index(Request $request)
    {
        $query = AdminQuery::parse($request->all());
        return ApiResponse::success((new PlanAdminService())->getList($query));
    }
}
