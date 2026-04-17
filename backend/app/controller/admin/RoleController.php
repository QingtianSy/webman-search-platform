<?php

namespace app\controller\admin;

use app\service\admin\RoleAdminService;
use support\ApiResponse;

class RoleController
{
    public function index()
    {
        return ApiResponse::success((new RoleAdminService())->getList());
    }
}
