<?php

namespace app\controller\admin;

use app\service\admin\ApiSourceAdminService;
use app\validate\admin\ApiSourceValidate;
use support\ApiResponse;
use support\Request;

class ApiSourceManageController
{
    public function index()
    {
        return ApiResponse::success((new ApiSourceAdminService())->getList());
    }

    public function detail(Request $request)
    {
        $id = (new ApiSourceValidate())->id($request->all());
        return ApiResponse::success((new ApiSourceAdminService())->detail($id));
    }

    public function test(Request $request)
    {
        $id = (new ApiSourceValidate())->id($request->all());
        return ApiResponse::success((new ApiSourceAdminService())->test($id));
    }
}
