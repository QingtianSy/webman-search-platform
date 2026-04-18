<?php

namespace app\controller\admin;

use app\service\admin\CollectAdminService;
use app\validate\admin\CollectTaskValidate;
use support\ApiResponse;
use support\Request;

class CollectManageController
{
    public function tasks()
    {
        return json([
            'code' => 1,
            'msg' => 'success',
            'data' => [
                'list' => [
                    [
                        'task_no' => 'CT202604150001',
                        'status' => 2,
                        'course_count' => 1,
                        'question_count' => 2,
                        'runner_script' => 'pending://collect-core-script'
                    ]
                ],
                'total' => 1,
                'page' => 1,
                'page_size' => 20
            ],
            'request_id' => ''
        ]);
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
