<?php

namespace app\controller\admin;

use app\common\admin\AdminPage;
use app\service\admin\MenuAdminService;
use support\ApiResponse;
use support\Request;

class MenuController
{
    public function index(Request $request)
    {
        [$page, $pageSize] = AdminPage::parse($request->all());
        return ApiResponse::success((new MenuAdminService())->getList($page, $pageSize));
    }
}
