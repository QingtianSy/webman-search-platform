<?php

namespace app\controller\admin;

use app\service\admin\UserAdminService;
use app\validate\admin\AdminQueryValidate;
use support\ApiResponse;
use support\Request;

class UserController
{
    public function index(Request $request)
    {
        $query = (new AdminQueryValidate())->list($request->get());
        return ApiResponse::success((new UserAdminService())->getList($query));
    }
}
