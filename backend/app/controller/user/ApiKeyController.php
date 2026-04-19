<?php

namespace app\controller\user;

use app\common\user\UserListBuilder;
use app\common\user\UserQuery;
use app\repository\mysql\OperateLogRepository;
use app\service\user\ApiKeyService;
use app\validate\user\ApiKeyValidate;
use support\ApiResponse;
use support\Request;

class ApiKeyController
{
    public function index(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $query = UserQuery::parse($request->get());
        $list = (new ApiKeyService())->listByUserId($userId);
        return ApiResponse::success(UserListBuilder::make($list, $query['page'], $query['page_size']));
    }

    public function detail(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $id = (new ApiKeyValidate())->id($request->get());
        return ApiResponse::success((new ApiKeyService())->detailById($userId, $id));
    }

    public function create(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $data = (new ApiKeyValidate())->create($request->post());
        $result = (new ApiKeyService())->create($userId, $data['app_name']);
        (new OperateLogRepository())->create(['user_id' => $userId, 'module' => 'api_key', 'action' => 'create', 'content' => "创建API Key: {$data['app_name']}", 'ip' => $request->getRealIp()]);
        return ApiResponse::success($result, '创建成功');
    }

    public function toggle(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $data = (new ApiKeyValidate())->toggle($request->post());
        $result = (new ApiKeyService())->toggle($userId, $data['id'], $data['status']);
        $action = $data['status'] === 1 ? '启用' : '禁用';
        (new OperateLogRepository())->create(['user_id' => $userId, 'module' => 'api_key', 'action' => 'toggle', 'content' => "{$action}API Key ID:{$data['id']}", 'ip' => $request->getRealIp()]);
        return ApiResponse::success($result, '状态已切换');
    }

    public function delete(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $id = (new ApiKeyValidate())->id($request->get());
        $result = (new ApiKeyService())->delete($userId, $id);
        (new OperateLogRepository())->create(['user_id' => $userId, 'module' => 'api_key', 'action' => 'delete', 'content' => "删除API Key ID:{$id}", 'ip' => $request->getRealIp()]);
        return ApiResponse::success($result, '删除成功');
    }
}
