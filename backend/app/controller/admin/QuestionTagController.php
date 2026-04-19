<?php

namespace app\controller\admin;

use app\common\admin\AdminId;
use app\common\admin\AdminQuery;
use app\service\admin\QuestionTagAdminService;
use app\validate\admin\QuestionTagValidate;
use support\ApiResponse;
use support\Request;

class QuestionTagController
{
    public function index(Request $request)
    {
        $query = AdminQuery::parse($request->get());
        return ApiResponse::success((new QuestionTagAdminService())->getList($query));
    }

    public function create(Request $request)
    {
        $data = (new QuestionTagValidate())->create($request->post());
        return ApiResponse::success((new QuestionTagAdminService())->create($data), '标签创建成功');
    }

    public function update(Request $request)
    {
        $data = (new QuestionTagValidate())->update($request->post());
        return ApiResponse::success((new QuestionTagAdminService())->update($data['id'], $data), '标签更新成功');
    }

    public function delete(Request $request)
    {
        $id = AdminId::parse($request->get(), 'id', '标签ID');
        return ApiResponse::success((new QuestionTagAdminService())->delete($id), '标签删除成功');
    }
}
