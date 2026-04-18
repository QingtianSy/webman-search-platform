<?php

namespace app\controller\admin;

use app\service\admin\MenuAdminService;
use app\validate\admin\AdminQueryValidate;
use support\ApiResponse;
use support\Request;

class MenuController
{
    public function index(Request $request)
    {
        $query = (new AdminQueryValidate())->list($request->get());
        return ApiResponse::success((new MenuAdminService())->getList($query));
    }
}
