<?php

namespace app\service\admin;

use app\repository\mysql\CollectTaskDetailRepository;
use app\repository\mysql\CollectTaskRepository;
use app\common\admin\AdminListBuilder;

class CollectAdminService
{
    public function getList(): array
    {
        $list = (new CollectTaskRepository())->listByUserId(1);
        return AdminListBuilder::make($list);
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
