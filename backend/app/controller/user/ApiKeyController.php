<?php

namespace app\controller\user;

use app\common\CurrentUser;
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
        $userId = CurrentUser::id($request);
        $query = UserQuery::parse($request->get());
        $list = (new ApiKeyService())->listByUserId($userId);
        return ApiResponse::success(UserListBuilder::make($list, $query['page'], $query['page_size']));
    }

    public function detail(Request $request)
    {
        $userId = CurrentUser::id($request);
        $id = (new ApiKeyValidate())->id($request->get());
        return ApiResponse::success((new ApiKeyService())->detailById($userId, $id));
    }

    public function create(Request $request)
    {
        $userId = CurrentUser::id($request);
        $data = (new ApiKeyValidate())->create($request->post());
        return ApiResponse::success((new ApiKeyService())->mockCreate($userId, $data['app_name']), '模拟创建成功');
    }

    public function toggle(Request $request)
    {
        $data = (new ApiKeyValidate())->toggle($request->post());
        return ApiResponse::success((new ApiKeyService())->toggle($data['id'], $data['status']), '状态已切换');
    }

    public function delete(Request $request)
    {
        $id = (new ApiKeyValidate())->id($request->get());
        return ApiResponse::success((new ApiKeyService())->delete($id), '删除成功');
    }
}
