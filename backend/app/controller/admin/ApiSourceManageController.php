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
        $result = (new ApiSourceAdminService())->detail($id);
        if (empty($result)) {
            return ApiResponse::error(40004, '接口源不存在');
        }
        return ApiResponse::success($result);
    }

    public function test(Request $request)
    {
        $id = (new ApiSourceValidate())->id($request->post());
        $result = (new ApiSourceAdminService())->test($id);
        if (!($result['success'] ?? false)) {
            return ApiResponse::error(50000, $result['data']['message'] ?? '接口源测试失败', $result);
        }
        return ApiResponse::success($result, '接口源测试完成');
    }
}
