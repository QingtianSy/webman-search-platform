<?php

namespace app\controller\admin;

use app\common\admin\AdminId;
use app\service\admin\MenuAdminService;
use app\validate\admin\AdminQueryValidate;
use app\validate\admin\MenuValidate;
use support\ApiResponse;
use support\Request;

class MenuController
{
    public function index(Request $request)
    {
        $query = (new AdminQueryValidate())->list($request->get());
        return ApiResponse::success((new MenuAdminService())->getList($query));
    }

    public function create(Request $request)
    {
        $data = (new MenuValidate())->create($request->post());
        return ApiResponse::success((new MenuAdminService())->create($data), '菜单创建成功');
    }

    public function update(Request $request)
    {
        $data = (new MenuValidate())->update($request->post());
        return ApiResponse::success((new MenuAdminService())->update($data['id'], $data), '菜单更新成功');
    }

    public function delete(Request $request)
    {
        $id = AdminId::parse($request->get(), 'id', '菜单ID');
        return ApiResponse::success((new MenuAdminService())->delete($id), '菜单删除成功');
    }
}
