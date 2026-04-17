<?php

namespace app\controller\admin;

use app\common\admin\AdminQuery;
use app\service\admin\ApiSourceAdminService;
use app\validate\admin\ApiSourceValidate;
use support\ApiResponse;
use support\Request;

class ApiSourceManageController
{
    public function index(Request $request)
    {
        $query = AdminQuery::parse($request->all());
        return ApiResponse::success((new ApiSourceAdminService())->getList($query));
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
