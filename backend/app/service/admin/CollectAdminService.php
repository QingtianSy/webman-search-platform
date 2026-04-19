<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\repository\mysql\CollectTaskDetailRepository;
use app\repository\mysql\CollectTaskRepository;

class CollectAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['page' => 1, 'page_size' => 20];
        $repo = new CollectTaskRepository();
        $list = isset($query['user_id']) && $query['user_id'] > 0
            ? $repo->listByUserId((int) $query['user_id'])
            : $repo->all();

        return AdminListBuilder::make($list, (int) $query['page'], (int) $query['page_size']);
    }

    public function detail(string $taskNo): array
    {
        return (new CollectTaskDetailRepository())->findByTaskNo($taskNo);
    }

    public function stop(string $taskNo): array
    {
        $repo = new CollectTaskRepository();
        $task = $repo->findByTaskNo($taskNo);
        if ($task && !empty($task['runner_script'])) {
            $pid = (int) $task['runner_script'];
            if ($pid > 0) {
                @exec("kill {$pid} 2>/dev/null");
            }
        }
        $repo->updateStatus($taskNo, 4, '手动停止');
        return [
            'success' => true,
            'action' => 'stop',
            'task_no' => $taskNo,
        ];
    }

    public function retry(string $taskNo): array
    {
        $repo = new CollectTaskRepository();
        $repo->updateStatus($taskNo, 0, '');
        $repo->clearRunnerScript($taskNo);
        return [
            'success' => true,
            'action' => 'retry',
            'task_no' => $taskNo,
        ];
    }
}
