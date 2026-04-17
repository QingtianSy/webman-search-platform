<?php

namespace app\controller\admin;

use app\common\admin\AdminQuery;
use app\service\admin\PermissionAdminService;
use support\ApiResponse;
use support\Request;

class PermissionController
{
    public function index(Request $request)
    {
        $query = AdminQuery::parse($request->all());
        return ApiResponse::success((new PermissionAdminService())->getList($query));
    }
}
