<?php

namespace app\service\admin;

use app\repository\mysql\CollectTaskDetailRepository;
use app\repository\mysql\CollectTaskRepository;

class CollectAdminService
{
    public function getList(array $query = []): array
    {
        $repo = new CollectTaskRepository();
        $list = isset($query['user_id']) && $query['user_id'] > 0
            ? $repo->listByUserId((int) $query['user_id'])
            : $repo->all();

        return [
            'list' => $list,
            'total' => count($list),
            'page' => $query['page'] ?? 1,
            'page_size' => $query['page_size'] ?? 20,
        ];
    }

    public function detail(string $taskNo): array
    {
        return (new CollectTaskDetailRepository())->findByTaskNo($taskNo);
    }

    public function stop(string $taskNo): array
    {
        (new CollectTaskRepository())->updateStatus($taskNo, 4, '手动停止');
        return [
            'success' => true,
            'action' => 'stop',
            'task_no' => $taskNo,
        ];
    }

    public function retry(string $taskNo): array
    {
        (new CollectTaskRepository())->updateStatus($taskNo, 1, '');
        return [
            'success' => true,
            'action' => 'retry',
            'task_no' => $taskNo,
        ];
    }
}
