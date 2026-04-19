<?php

namespace app\controller\user;

use app\common\user\UserListBuilder;
use app\common\user\UserQuery;
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
        return ApiResponse::success((new ApiKeyService())->create($userId, $data['app_name']), '创建成功');
    }

    public function toggle(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $data = (new ApiKeyValidate())->toggle($request->post());
        return ApiResponse::success((new ApiKeyService())->toggle($userId, $data['id'], $data['status']), '状态已切换');
    }

    public function delete(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $id = (new ApiKeyValidate())->id($request->get());
        return ApiResponse::success((new ApiKeyService())->delete($userId, $id), '删除成功');
    }
}
