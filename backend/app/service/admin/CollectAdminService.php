<?php

namespace app\service\admin;

use app\exception\BusinessException;
use app\repository\mysql\CollectTaskDetailRepository;
use app\repository\mysql\CollectTaskRepository;
use support\Pagination;

class CollectAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['page' => 1, 'page_size' => 20];
        $repo = new CollectTaskRepository();
        $userId = isset($query['user_id']) && $query['user_id'] > 0 ? (int) $query['user_id'] : null;
        $total = $repo->countAll($userId);
        $list = $repo->findPage((int) $query['page'], (int) $query['page_size'], $userId);
        return Pagination::format($list, $total, (int) $query['page'], (int) $query['page_size']);
    }

    public function detail(string $taskNo): array
    {
        return (new CollectTaskDetailRepository())->findByTaskNo($taskNo);
    }

    public function stop(string $taskNo): array
    {
        $repo = new CollectTaskRepository();
        $task = $repo->findByTaskNo($taskNo);
        if (!$task) {
            throw new BusinessException('采集任务不存在', 40001);
        }
        $status = (int) $task['status'];
        if (!in_array($status, [0, 1], true)) {
            throw new BusinessException('当前任务状态不可停止', 40001);
        }
        if (!empty($task['runner_script'])) {
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
        $task = $repo->findByTaskNo($taskNo);
        if (!$task) {
            throw new BusinessException('采集任务不存在', 40001);
        }
        $status = (int) $task['status'];
        if (!in_array($status, [3, 4], true)) {
            throw new BusinessException('当前任务状态不可重试', 40001);
        }
        $repo->updateStatus($taskNo, 0, '');
        $repo->clearRunnerScript($taskNo);
        return [
            'success' => true,
            'action' => 'retry',
            'task_no' => $taskNo,
        ];
    }
}
