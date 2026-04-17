<?php

namespace app\controller\admin;

use app\service\admin\MenuAdminService;
use support\ApiResponse;

class MenuController
{
    public function index()
    {
        return ApiResponse::success((new MenuAdminService())->getList());
    }
}
