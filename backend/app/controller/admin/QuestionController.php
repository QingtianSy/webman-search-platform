<?php

namespace app\controller\admin;

use app\common\admin\AdminQuery;
use app\service\admin\QuestionAdminService;
use app\validate\admin\QuestionValidate;
use support\ApiResponse;
use support\Request;

class QuestionController
{
    public function index(Request $request)
    {
        $query = AdminQuery::parse($request->get());
        return ApiResponse::success((new QuestionAdminService())->getList($query), '题目列表接口已接入后台服务层');
    }

    public function detail(Request $request)
    {
        $id = (new QuestionValidate())->id($request->get());
        return ApiResponse::success((new QuestionAdminService())->detail($id));
    }

    public function create(Request $request)
    {
        $data = (new QuestionValidate())->create($request->post());
        return ApiResponse::success((new QuestionAdminService())->create($data), '题目新增接口骨架已创建');
    }

    public function update(Request $request)
    {
        $data = (new QuestionValidate())->update($request->post());
        return ApiResponse::success((new QuestionAdminService())->update($data['id'], ['stem' => $data['stem']]), '题目更新骨架已创建');
    }

    public function delete(Request $request)
    {
        $id = (new QuestionValidate())->id($request->get());
        return ApiResponse::success((new QuestionAdminService())->delete($id), '题目删除骨架已创建');
    }
}
