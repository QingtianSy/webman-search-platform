<?php

namespace app\controller\user;

use app\service\user\ApiSourceService;
use app\validate\user\ApiSourceValidate;
use support\ApiResponse;
use support\Request;

class ApiSourceController
{
    public function index(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $query = $request->get();
        return ApiResponse::success((new ApiSourceService())->getList($userId, $query));
    }

    public function detail(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $id = (new ApiSourceValidate())->id($request->get());
        $row = (new ApiSourceService())->detail($userId, $id);
        if (empty($row)) {
            return ApiResponse::error(40004, '接口源不存在');
        }
        return ApiResponse::success($row);
    }

    public function create(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $data = (new ApiSourceValidate())->create($request->post());
        $result = (new ApiSourceService())->create($userId, $data);
        if (empty($result)) {
            return ApiResponse::error(50000, '创建失败');
        }
        return ApiResponse::success($result, '接口源创建成功');
    }

    public function update(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $data = (new ApiSourceValidate())->update($request->post());
        $id = $data['id'];
        unset($data['id']);
        $result = (new ApiSourceService())->update($userId, $id, $data);
        if (empty($result)) {
            return ApiResponse::error(40004, '接口源不存在或更新失败');
        }
        return ApiResponse::success($result, '接口源更新成功');
    }

    public function delete(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $id = (new ApiSourceValidate())->id($request->get());
        $ok = (new ApiSourceService())->delete($userId, $id);
        if (!$ok) {
            return ApiResponse::error(40004, '接口源不存在');
        }
        return ApiResponse::success(['id' => $id], '接口源删除成功');
    }

    public function test(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $id = (new ApiSourceValidate())->id($request->post());
        return ApiResponse::success((new ApiSourceService())->test($userId, $id), '接口源测试完成');
    }
}
