<?php

namespace app\controller\admin;

use app\service\admin\RoleAdminService;
use app\validate\admin\AdminQueryValidate;
use support\ApiResponse;
use support\Request;

class RoleController
{
    public function index(Request $request)
    {
        $query = (new AdminQueryValidate())->list($request->get());
        return ApiResponse::success((new RoleAdminService())->getList($query));
    }
}
