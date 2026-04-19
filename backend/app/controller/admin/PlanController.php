<?php

namespace app\controller\admin;

use app\common\admin\AdminId;
use app\service\admin\PlanAdminService;
use app\validate\admin\AdminQueryValidate;
use app\validate\admin\PlanValidate;
use support\ApiResponse;
use support\Request;

class PlanController
{
    public function index(Request $request)
    {
        $query = (new AdminQueryValidate())->list($request->get());
        return ApiResponse::success((new PlanAdminService())->getList($query));
    }

    public function create(Request $request)
    {
        $data = (new PlanValidate())->create($request->post());
        return ApiResponse::success((new PlanAdminService())->create($data), '套餐创建成功');
    }

    public function update(Request $request)
    {
        $data = (new PlanValidate())->update($request->post());
        return ApiResponse::success((new PlanAdminService())->update($data['id'], $data), '套餐更新成功');
    }

    public function delete(Request $request)
    {
        $id = AdminId::parse($request->get(), 'id', '套餐ID');
        return ApiResponse::success((new PlanAdminService())->delete($id), '套餐删除成功');
    }
}
