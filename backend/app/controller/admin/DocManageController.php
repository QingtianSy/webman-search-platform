<?php

namespace app\controller\admin;

use app\common\admin\AdminQuery;
use app\service\admin\DocAdminService;
use support\ApiResponse;
use support\Request;

class DocManageController
{
    public function articles(Request $request)
    {
        $query = AdminQuery::parse($request->get());
        return ApiResponse::success((new DocAdminService())->getList($query));
    }

    public function create()
    {
        return json(['code' => 1, 'msg' => 'success', 'data' => []]);
    }

    public function update()
    {
        return json(['code' => 1, 'msg' => 'success', 'data' => []]);
    }

    public function delete()
    {
        return json(['code' => 1, 'msg' => 'success', 'data' => ['deleted' => true]]);
    }
}
