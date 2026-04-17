<?php

namespace app\controller\admin;

use app\common\admin\AdminQuery;
use app\service\admin\QuestionTagAdminService;
use support\ApiResponse;
use support\Request;

class QuestionTagController
{
    public function index(Request $request)
    {
        $query = AdminQuery::parse($request->all());
        return ApiResponse::success((new QuestionTagAdminService())->getList($query));
    }
}
