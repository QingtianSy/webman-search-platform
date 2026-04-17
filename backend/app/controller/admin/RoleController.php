<?php

namespace app\controller\admin;

use app\common\admin\AdminQuery;
use app\service\admin\RoleAdminService;
use support\ApiResponse;
use support\Request;

class RoleController
{
    public function index(Request $request)
    {
        $query = AdminQuery::parse($request->all());
        return ApiResponse::success((new RoleAdminService())->getList($query));
    }
}
