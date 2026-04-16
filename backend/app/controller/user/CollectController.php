<?php

namespace app\controller\user;

use app\repository\mysql\CollectAccountRepository;
use app\repository\mysql\CollectTaskDetailRepository;
use app\repository\mysql\CollectTaskRepository;
use support\ApiResponse;
use support\Pagination;
use support\Request;

class CollectController
{
    public function accounts(): array
    {
        $list = (new CollectAccountRepository())->listByUserId(1);
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }

    public function tasks(): array
    {
        $list = (new CollectTaskRepository())->listByUserId(1);
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }

    public function detail(Request $request): array
    {
                $taskNo = (string) $request->input('task_no', '');
        return ApiResponse::success((new CollectTaskDetailRepository())->findByTaskNo($taskNo));
    }
}
