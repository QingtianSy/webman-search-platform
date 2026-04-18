<?php

namespace app\service\admin;

use app\repository\mysql\CollectTaskDetailRepository;
use app\repository\mysql\CollectTaskRepository;

class CollectAdminService
{
    public function getList(array $query = []): array
    {
        $list = (new CollectTaskRepository())->listByUserId(1);

        return [
            'list' => $list,
            'total' => count($list),
            'page' => 1,
            'page_size' => 20,
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
