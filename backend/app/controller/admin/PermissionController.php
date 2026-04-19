<?php

namespace app\controller\admin;

use app\common\admin\AdminId;
use app\service\admin\PermissionAdminService;
use app\validate\admin\AdminQueryValidate;
use app\validate\admin\PermissionValidate;
use support\ApiResponse;
use support\Request;

class PermissionController
{
    public function index(Request $request)
    {
        $query = (new AdminQueryValidate())->list($request->get());
        return ApiResponse::success((new PermissionAdminService())->getList($query));
    }

    public function create(Request $request)
    {
        $data = (new PermissionValidate())->create($request->post());
        return ApiResponse::success((new PermissionAdminService())->create($data), '权限创建成功');
    }

    public function update(Request $request)
    {
        $data = (new PermissionValidate())->update($request->post());
        return ApiResponse::success((new PermissionAdminService())->update($data['id'], $data), '权限更新成功');
    }

    public function delete(Request $request)
    {
        $id = AdminId::parse($request->get(), 'id', '权限ID');
        return ApiResponse::success((new PermissionAdminService())->delete($id), '权限删除成功');
    }
}
