<?php

namespace app\controller\admin;

use app\common\admin\AdminPage;
use app\service\admin\UserAdminService;
use support\ApiResponse;
use support\Request;

class UserController
{
    public function index(Request $request)
    {
        [$page, $pageSize] = AdminPage::parse($request->all());
        return ApiResponse::success((new UserAdminService())->getList($page, $pageSize));
    }
}
