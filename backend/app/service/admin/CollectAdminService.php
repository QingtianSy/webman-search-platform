<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\repository\mysql\CollectTaskDetailRepository;
use app\repository\mysql\CollectTaskRepository;

class CollectAdminService
{
    public function getList(array $query = []): array
    {
        $page = $query['page'] ?? 1;
        $pageSize = $query['page_size'] ?? 20;
        $list = (new CollectTaskRepository())->listByUserId(1);
        return AdminListBuilder::make($list, $page, $pageSize);
    }

    public function detail(string $taskNo): array
    {
        return (new CollectTaskDetailRepository())->findByTaskNo($taskNo);
    }

    public function stop(string $taskNo): array
    {
        return (new CollectTaskRepository())->updateStatus($taskNo, 4, '手动停止');
    }

    public function retry(string $taskNo): array
    {
        return (new CollectTaskRepository())->updateStatus($taskNo, 1, '');
    }
}
