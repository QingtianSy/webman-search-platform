<?php

namespace app\controller\admin;

use app\common\admin\AdminQuery;
use app\service\admin\SearchLogAdminService;
use support\ApiResponse;
use support\Request;

class SearchLogController
{
    public function index(Request $request)
    {
        $query = AdminQuery::parse($request->get());
        return ApiResponse::success((new SearchLogAdminService())->getList($query));
    }
}
