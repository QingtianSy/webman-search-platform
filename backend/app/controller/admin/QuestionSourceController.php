<?php

namespace app\controller\admin;

use app\common\admin\AdminQuery;
use app\service\admin\QuestionSourceAdminService;
use support\ApiResponse;
use support\Request;

class QuestionSourceController
{
    public function index(Request $request)
    {
        $query = AdminQuery::parse($request->all());
        return ApiResponse::success((new QuestionSourceAdminService())->getList($query));
    }
}
