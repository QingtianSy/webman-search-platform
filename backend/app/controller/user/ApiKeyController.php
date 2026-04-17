<?php

namespace app\controller\user;

use app\common\CurrentUser;
use app\service\open\ApiKeyService;
use support\ApiResponse;
use support\Request;

class ApiKeyController
{
    public function index(Request $request)
    {
        $userId = CurrentUser::id($request);
        $service = new ApiKeyService();
        $list = $service->listByUserId($userId);
        return ApiResponse::success([
            'list' => $list,
            'total' => count($list),
            'page' => 1,
            'page_size' => 20,
        ]);
    }

    public function detail(Request $request)
    {
        $userId = CurrentUser::id($request);
        $id = (int) $request->input('id', 0);
        return ApiResponse::success((new ApiKeyService())->detailById($userId, $id));
    }

    public function create(Request $request)
    {
        $userId = CurrentUser::id($request);
        $appName = (string) $request->input('app_name', '默认应用');
        return ApiResponse::success((new ApiKeyService())->mockCreate($userId, $appName), '模拟创建成功');
    }

    public function toggle(Request $request)
    {
        $id = (int) $request->input('id', 0);
        $status = (int) $request->input('status', 1);
        return ApiResponse::success((new ApiKeyService())->toggle($id, $status), '状态已切换');
    }

    public function delete(Request $request)
    {
        $id = (int) $request->input('id', 0);
        return ApiResponse::success((new ApiKeyService())->delete($id), '删除成功');
    }
}
