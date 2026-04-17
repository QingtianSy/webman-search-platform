<?php

namespace app\controller\admin;

use app\service\admin\PermissionAdminService;
use app\validate\admin\AdminQueryValidate;
use support\ApiResponse;
use support\Request;

class PermissionController
{
    public function index(Request $request)
    {
        $query = (new AdminQueryValidate())->list($request->get());
        return ApiResponse::success((new PermissionAdminService())->getList($query));
    }
}
