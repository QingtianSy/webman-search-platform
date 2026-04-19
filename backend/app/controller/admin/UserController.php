<?php

namespace app\controller\admin;

use app\common\admin\AdminId;
use app\service\admin\UserAdminService;
use app\validate\admin\AdminQueryValidate;
use app\validate\admin\UserValidate;
use support\ApiResponse;
use support\Request;

class UserController
{
    public function index(Request $request)
    {
        $query = (new AdminQueryValidate())->list($request->get());
        return ApiResponse::success((new UserAdminService())->getList($query));
    }

    public function create(Request $request)
    {
        $data = (new UserValidate())->create($request->post());
        return ApiResponse::success((new UserAdminService())->create($data), '用户创建成功');
    }

    public function update(Request $request)
    {
        $data = (new UserValidate())->update($request->post());
        return ApiResponse::success((new UserAdminService())->update($data['id'], $data), '用户更新成功');
    }

    public function delete(Request $request)
    {
        $id = AdminId::parse($request->get(), 'id', '用户ID');
        return ApiResponse::success((new UserAdminService())->delete($id), '用户删除成功');
    }

    public function toggleStatus(Request $request)
    {
        $id = AdminId::parse($request->post(), 'id', '用户ID');
        return ApiResponse::success((new UserAdminService())->toggleStatus($id), '状态切换成功');
    }

    public function assignRoles(Request $request)
    {
        $data = (new UserValidate())->assignRoles($request->post());
        return ApiResponse::success(
            (new UserAdminService())->assignRoles($data['user_id'], $data['role_ids']),
            '角色分配成功'
        );
    }
}
