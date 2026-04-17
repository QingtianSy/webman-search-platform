<?php

namespace app\controller\admin;

use app\service\admin\PlanAdminService;
use app\validate\admin\AdminQueryValidate;
use support\ApiResponse;
use support\Request;

class PlanController
{
    public function index(Request $request)
    {
        $query = (new AdminQueryValidate())->list($request->get());
        return ApiResponse::success((new PlanAdminService())->getList($query));
    }
}
