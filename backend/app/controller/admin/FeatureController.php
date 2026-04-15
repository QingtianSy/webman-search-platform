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

    public function create(?Request $request = null): array
    {
        $request ??= new Request();
        $created = (new DocArticleRepository())->create([
            'category_id' => (int) $request->input('category_id', 1),
            'slug' => (string) $request->input('slug', 'new-doc'),
            'title' => (string) $request->input('title', '新文档'),
            'summary' => (string) $request->input('summary', ''),
            'content_md' => (string) $request->input('content_md', ''),
            'status' => 1,
        ]);
        return ApiResponse::success($created, '文档创建骨架已创建');
    }

    public function update(?Request $request = null): array
    {
        $request ??= new Request();
        $id = (int) $request->input('id', 0);
        $updated = (new DocArticleRepository())->update($id, [
            'title' => (string) $request->input('title', ''),
            'summary' => (string) $request->input('summary', ''),
            'content_md' => (string) $request->input('content_md', ''),
        ]);
        return ApiResponse::success($updated, '文档更新骨架已创建');
    }

    public function delete(?Request $request = null): array
    {
        $request ??= new Request();
        $id = (int) $request->input('id', 0);
        return ApiResponse::success(['deleted' => true, 'id' => $id], '文档删除骨架已创建');
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

    public function stop(?Request $request = null): array
    {
        $request ??= new Request();
        $taskNo = (string) $request->input('task_no', '');
        return ApiResponse::success((new CollectTaskRepository())->updateStatus($taskNo, 4, '手动停止'), '任务停止骨架已创建');
    }

    public function retry(?Request $request = null): array
    {
        $request ??= new Request();
        $taskNo = (string) $request->input('task_no', '');
        return ApiResponse::success((new CollectTaskRepository())->updateStatus($taskNo, 1, ''), '任务重试骨架已创建');
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

    public function test(?Request $request = null): array
    {
        $request ??= new Request();
        $id = (int) $request->input('id', 0);
        return ApiResponse::success((new ApiSourceRepository())->test($id));
    }
}

class SystemConfigController
{
    public function index(): array
    {
        $list = (new SystemConfigRepository())->all();
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }

    public function update(?Request $request = null): array
    {
        $request ??= new Request();
        $key = (string) $request->input('config_key', '');
        $value = (string) $request->input('config_value', '');
        return ApiResponse::success((new SystemConfigRepository())->updateByKey($key, $value), '系统配置更新骨架已创建');
    }
}
