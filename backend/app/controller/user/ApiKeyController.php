<?php

namespace app\controller\user;

use app\common\user\UserQuery;
use app\repository\mysql\OperateLogRepository;
use app\service\user\ApiKeyService;
use app\validate\user\ApiKeyValidate;
use support\ApiResponse;
use support\Pagination;
use support\Request;

class ApiKeyController
{
    public function index(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $query = UserQuery::parse($request->get());
        $paged = (new ApiKeyService())->listPaginated($userId, $query['page'], $query['page_size']);
        return ApiResponse::success(Pagination::format($paged['list'], $paged['total'], $query['page'], $query['page_size']));
    }

    public function detail(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $id = (new ApiKeyValidate())->id($request->get());
        $result = (new ApiKeyService())->detailById($userId, $id);
        if (empty($result)) {
            return ApiResponse::error(40004, 'API Key 不存在');
        }
        return ApiResponse::success($result);
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

    public function setDefault(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $id = (int) $request->post('id', 0);
        if ($id <= 0) {
            return ApiResponse::error(40001, 'API Key ID 不能为空');
        }
        (new ApiKeyService())->setDefault($userId, $id);
        (new OperateLogRepository())->create(['user_id' => $userId, 'module' => 'api_key', 'action' => 'set_default', 'content' => "设为默认 ID:{$id}", 'ip' => $request->getRealIp()]);
        return ApiResponse::success(null, '已设为默认');
    }

    // 明文 secret 只返这一次，前端需立即提示用户复制保存，服务端仅保存 bcrypt hash。
    public function regenerate(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $id = (int) $request->post('id', 0);
        if ($id <= 0) {
            return ApiResponse::error(40001, 'API Key ID 不能为空');
        }
        $result = (new ApiKeyService())->regenerate($userId, $id);
        (new OperateLogRepository())->create(['user_id' => $userId, 'module' => 'api_key', 'action' => 'regenerate', 'content' => "重置 Secret ID:{$id}", 'ip' => $request->getRealIp()]);
        return ApiResponse::success($result, 'Secret 已重置，请立即保存');
    }
}
