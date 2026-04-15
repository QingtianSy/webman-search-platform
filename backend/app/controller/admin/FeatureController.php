<?php

namespace app\controller\admin;

use app\repository\mysql\ApiSourceRepository;
use app\repository\mysql\CollectTaskDetailRepository;
use app\repository\mysql\CollectTaskRepository;
use app\repository\mysql\DocArticleRepository;
use app\repository\mysql\SystemConfigRepository;
use support\ApiResponse;
use support\Pagination;
use support\Request;

class DocManageController
{
    public function articles(): array
    {
        $list = (new DocArticleRepository())->all();
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }
}

class CollectManageController
{
    public function tasks(): array
    {
        $list = (new CollectTaskRepository())->listByUserId(1);
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }

    public function detail(?Request $request = null): array
    {
        $request ??= new Request();
        $taskNo = (string) $request->input('task_no', '');
        return ApiResponse::success((new CollectTaskDetailRepository())->findByTaskNo($taskNo));
    }
}

class ApiSourceManageController
{
    public function index(): array
    {
        $list = (new ApiSourceRepository())->all();
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }

    public function detail(?Request $request = null): array
    {
        $request ??= new Request();
        $id = (int) $request->input('id', 0);
        return ApiResponse::success((new ApiSourceRepository())->findById($id));
    }
}

class SystemConfigController
{
    public function index(): array
    {
        $list = (new SystemConfigRepository())->all();
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }
}
