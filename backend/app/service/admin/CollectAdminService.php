<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\repository\mysql\CollectTaskDetailRepository;
use app\repository\mysql\CollectTaskRepository;

class CollectAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['keyword' => '', 'page' => 1, 'page_size' => 20];
        $keyword = trim((string) $query['keyword']);
        $page = (int) $query['page'];
        $pageSize = (int) $query['page_size'];

        $list = (new CollectTaskRepository())->listByUserId(1);
        if ($keyword !== '') {
            $list = array_values(array_filter($list, function ($row) use ($keyword) {
                return str_contains((string) ($row['task_no'] ?? ''), $keyword)
                    || str_contains((string) ($row['type'] ?? ''), $keyword);
            }));
        }
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
