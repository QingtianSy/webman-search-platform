<?php

namespace app\controller\admin;

use app\common\admin\AdminPage;
use app\service\admin\PermissionAdminService;
use support\ApiResponse;
use support\Request;

class PermissionController
{
    public function index(Request $request)
    {
        [$page, $pageSize] = AdminPage::parse($request->all());
        return ApiResponse::success((new PermissionAdminService())->getList($page, $pageSize));
    }
}
