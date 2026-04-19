<?php

namespace app\controller\admin;

use app\common\admin\AdminId;
use app\service\admin\RoleAdminService;
use app\validate\admin\AdminQueryValidate;
use app\validate\admin\RoleValidate;
use support\ApiResponse;
use support\Request;

class RoleController
{
    public function index(Request $request)
    {
        $query = (new AdminQueryValidate())->list($request->get());
        return ApiResponse::success((new RoleAdminService())->getList($query));
    }

    public function create(Request $request)
    {
        $data = (new RoleValidate())->create($request->post());
        return ApiResponse::success((new RoleAdminService())->create($data), '角色创建成功');
    }

    public function update(Request $request)
    {
        $data = (new RoleValidate())->update($request->post());
        return ApiResponse::success((new RoleAdminService())->update($data['id'], $data), '角色更新成功');
    }

    public function delete(Request $request)
    {
        $id = AdminId::parse($request->get(), 'id', '角色ID');
        return ApiResponse::success((new RoleAdminService())->delete($id), '角色删除成功');
    }

    public function assignPermissions(Request $request)
    {
        $data = (new RoleValidate())->assignPermissions($request->post());
        return ApiResponse::success(
            (new RoleAdminService())->assignPermissions($data['role_id'], $data['permission_ids']),
            '权限分配成功'
        );
    }
}
