<?php

namespace app\controller\admin;

use app\common\admin\AdminId;
use app\common\admin\AdminQuery;
use app\service\admin\QuestionCategoryAdminService;
use app\validate\admin\QuestionCategoryValidate;
use support\ApiResponse;
use support\Request;

class QuestionCategoryController
{
    public function index(Request $request)
    {
        $query = AdminQuery::parse($request->get());
        return ApiResponse::success((new QuestionCategoryAdminService())->getList($query));
    }

    public function create(Request $request)
    {
        $data = (new QuestionCategoryValidate())->create($request->post());
        return ApiResponse::success((new QuestionCategoryAdminService())->create($data), '分类创建成功');
    }

    public function update(Request $request)
    {
        $data = (new QuestionCategoryValidate())->update($request->post());
        return ApiResponse::success((new QuestionCategoryAdminService())->update($data['id'], $data), '分类更新成功');
    }

    public function delete(Request $request)
    {
        $id = AdminId::parse($request->get(), 'id', '分类ID');
        return ApiResponse::success((new QuestionCategoryAdminService())->delete($id), '分类删除成功');
    }
}
