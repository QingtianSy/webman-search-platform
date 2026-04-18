<?php

namespace app\controller\admin;

use app\service\admin\CollectAdminService;
use app\validate\admin\CollectTaskValidate;
use support\ApiResponse;
use support\Request;

class CollectManageController
{
    public function tasks(Request $request)
    {
        $query = (new \app\validate\admin\AdminQueryValidate())->list($request->get());
        return ApiResponse::success((new CollectAdminService())->getList($query));
    }

    public function detail(Request $request)
    {
        $taskNo = (new CollectTaskValidate())->taskNo($request->post());
        return ApiResponse::success((new CollectAdminService())->detail($taskNo));
    }

    public function stop(Request $request)
    {
        $taskNo = (new CollectTaskValidate())->taskNo($request->post());
        return ApiResponse::success((new CollectAdminService())->stop($taskNo), '任务停止骨架已创建');
    }

    public function retry(Request $request)
    {
        $taskNo = (new CollectTaskValidate())->taskNo($request->post());
        return ApiResponse::success((new CollectAdminService())->retry($taskNo), '任务重试骨架已创建');
    }
}
