<?php

namespace app\controller\admin;

use app\service\admin\ApiSourceAdminService;
use app\validate\admin\AdminQueryValidate;
use app\validate\admin\ApiSourceValidate;
use support\ApiResponse;
use support\Request;

class ApiSourceManageController
{
    public function index(Request $request)
    {
        $query = (new AdminQueryValidate())->list($request->get());
        return ApiResponse::success((new ApiSourceAdminService())->getList($query));
    }

    public function detail(Request $request)
    {
        $id = (new ApiSourceValidate())->id($request->get());
        return ApiResponse::success((new ApiSourceAdminService())->detail($id));
    }

    public function test(Request $request)
    {
        $id = (new ApiSourceValidate())->id($request->post());
        return ApiResponse::success((new ApiSourceAdminService())->test($id));
    }
}
