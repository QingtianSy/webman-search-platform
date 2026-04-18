<?php

namespace app\controller\admin;

use app\common\admin\AdminQuery;
use app\service\admin\QuestionCategoryAdminService;
use support\ApiResponse;
use support\Request;

class QuestionCategoryController
{
    public function index(Request $request)
    {
        $query = AdminQuery::parse($request->get());
        return ApiResponse::success((new QuestionCategoryAdminService())->getList($query));
    }
}
