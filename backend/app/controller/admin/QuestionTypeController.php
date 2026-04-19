<?php

namespace app\controller\admin;

use app\common\admin\AdminId;
use app\common\admin\AdminQuery;
use app\service\admin\QuestionTypeAdminService;
use app\validate\admin\QuestionTypeValidate;
use support\ApiResponse;
use support\Request;

class QuestionTypeController
{
    public function index(Request $request)
    {
        $query = AdminQuery::parse($request->get());
        return ApiResponse::success((new QuestionTypeAdminService())->getList($query));
    }

    public function create(Request $request)
    {
        $data = (new QuestionTypeValidate())->create($request->post());
        return ApiResponse::success((new QuestionTypeAdminService())->create($data), '题型创建成功');
    }

    public function update(Request $request)
    {
        $data = (new QuestionTypeValidate())->update($request->post());
        return ApiResponse::success((new QuestionTypeAdminService())->update($data['id'], $data), '题型更新成功');
    }

    public function delete(Request $request)
    {
        $id = AdminId::parse($request->get(), 'id', '题型ID');
        return ApiResponse::success((new QuestionTypeAdminService())->delete($id), '题型删除成功');
    }
}
