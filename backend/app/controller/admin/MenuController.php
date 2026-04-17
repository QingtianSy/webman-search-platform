<?php

namespace app\controller\admin;

use app\common\admin\AdminQuery;
use app\service\admin\MenuAdminService;
use support\ApiResponse;
use support\Request;

class MenuController
{
    public function index(Request $request)
    {
        $query = AdminQuery::parse($request->all());
        return ApiResponse::success((new MenuAdminService())->getList($query));
    }
}
