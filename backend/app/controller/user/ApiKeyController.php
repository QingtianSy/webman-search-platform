<?php

namespace app\controller\user;

use app\service\open\ApiKeyService;
use support\ApiResponse;
use support\Request;

class ApiKeyController
{
    public function index(): array
    {
        $service = new ApiKeyService();
        $list = $service->listByUserId(1);
        return ApiResponse::success([
            'list' => $list,
            'total' => count($list),
            'page' => 1,
            'page_size' => 20,
        ]);
    }

    public function detail(Request $request): array
    {
                $id = (int) $request->input('id', 0);
        return ApiResponse::success((new ApiKeyService())->detailById(1, $id));
    }

    public function create(Request $request): array
    {
                $appName = (string) $request->input('app_name', '');
        return ApiResponse::success((new ApiKeyService())->mockCreate(1, $appName), '模拟创建成功');
    }

    public function toggle(Request $request): array
    {
                $id = (int) $request->input('id', 0);
        return ApiResponse::success(['id' => $id, 'status' => 0], '模拟切换成功');
    }

    public function delete(Request $request): array
    {
                $id = (int) $request->input('id', 0);
        return ApiResponse::success(['deleted' => (new ApiKeyService())->delete($id)], '模拟删除成功');
    }
}
