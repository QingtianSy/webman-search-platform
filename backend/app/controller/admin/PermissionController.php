<?php

namespace app\controller\admin;

use app\service\admin\PermissionAdminService;
use support\ApiResponse;

class PermissionController
{
    public function index()
    {
        return ApiResponse::success((new PermissionAdminService())->getList());
    }
}
