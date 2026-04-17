<?php

namespace app\controller\admin;

use app\common\admin\AdminQuery;
use app\service\admin\UserAdminService;
use support\ApiResponse;
use support\Request;

class UserController
{
    public function index(Request $request)
    {
        $query = AdminQuery::parse($request->all());
        return ApiResponse::success((new UserAdminService())->getList($query));
    }
}
