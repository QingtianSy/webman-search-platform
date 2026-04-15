<?php

namespace app\controller\admin;

use app\repository\mysql\CollectTaskDetailRepository;
use app\repository\mysql\CollectTaskRepository;
use support\ApiResponse;
use support\Pagination;
use support\InputRequest;

class CollectManageController
{
    public function tasks(): array
    {
        $list = (new CollectTaskRepository())->listByUserId(1);
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }

    public function detail(?InputRequest $request = null): array
    {
        $request ??= new InputRequest();
        $taskNo = (string) $request->input('task_no', '');
        return ApiResponse::success((new CollectTaskDetailRepository())->findByTaskNo($taskNo));
    }

    public function stop(?InputRequest $request = null): array
    {
        $request ??= new InputRequest();
        $taskNo = (string) $request->input('task_no', '');
        return ApiResponse::success((new CollectTaskRepository())->updateStatus($taskNo, 4, '手动停止'), '任务停止骨架已创建');
    }

    public function retry(?InputRequest $request = null): array
    {
        $request ??= new InputRequest();
        $taskNo = (string) $request->input('task_no', '');
        return ApiResponse::success((new CollectTaskRepository())->updateStatus($taskNo, 1, ''), '任务重试骨架已创建');
    }
}
