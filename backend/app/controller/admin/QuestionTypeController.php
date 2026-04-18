<?php

namespace app\controller\admin;

use app\common\admin\AdminQuery;
use app\service\admin\QuestionTypeAdminService;
use support\ApiResponse;
use support\Request;

class QuestionTypeController
{
    public function index(Request $request)
    {
        $query = AdminQuery::parse($request->get());
        return ApiResponse::success((new QuestionTypeAdminService())->getList($query));
    }
}
