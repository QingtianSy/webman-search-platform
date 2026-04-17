<?php

namespace app\controller\admin;

use app\service\admin\UserAdminService;
use support\ApiResponse;

class UserController
{
    public function index()
    {
        return ApiResponse::success((new UserAdminService())->getList());
    }
}
