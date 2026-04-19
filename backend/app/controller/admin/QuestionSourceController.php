<?php

namespace app\controller\admin;

use app\common\admin\AdminId;
use app\common\admin\AdminQuery;
use app\service\admin\QuestionSourceAdminService;
use app\validate\admin\QuestionSourceValidate;
use support\ApiResponse;
use support\Request;

class QuestionSourceController
{
    public function index(Request $request)
    {
        $query = AdminQuery::parse($request->get());
        return ApiResponse::success((new QuestionSourceAdminService())->getList($query));
    }

    public function create(Request $request)
    {
        $data = (new QuestionSourceValidate())->create($request->post());
        return ApiResponse::success((new QuestionSourceAdminService())->create($data), '来源创建成功');
    }

    public function update(Request $request)
    {
        $data = (new QuestionSourceValidate())->update($request->post());
        return ApiResponse::success((new QuestionSourceAdminService())->update($data['id'], $data), '来源更新成功');
    }

    public function delete(Request $request)
    {
        $id = AdminId::parse($request->get(), 'id', '来源ID');
        return ApiResponse::success((new QuestionSourceAdminService())->delete($id), '来源删除成功');
    }
}
