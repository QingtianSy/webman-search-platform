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
        try {
            $total = $repo->countAllStrict($userId);
            $list = $repo->findPageStrict((int) $query['page'], (int) $query['page_size'], $userId);
        } catch (\RuntimeException $e) {
            // 之前返回 0/[]，后台以为"没任务"，掩盖 DB 故障。
            throw new BusinessException('采集任务列表暂不可用，请稍后重试', 50001);
        }
        return Pagination::format($list, $total, (int) $query['page'], (int) $query['page_size']);
    }

    public function detail(string $taskNo): array
    {
        try {
            return (new CollectTaskDetailRepository())->findByTaskNoStrict($taskNo);
        } catch (\RuntimeException $e) {
            throw new BusinessException('采集任务详情暂不可用，请稍后重试', 50001);
        }
    }

    public function stop(string $taskNo): array
    {
        $repo = new CollectTaskRepository();
        try {
            $task = $repo->findByTaskNoStrict($taskNo);
        } catch (\RuntimeException $e) {
            // 之前用 findByTaskNo，DB 故障 → null → 误报 40001"任务不存在"。
            throw new BusinessException('采集任务服务暂不可用，请稍后重试', 50001);
        }
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
                $cmdline = [];
                @exec("ps -p {$pid} -o args= 2>/dev/null", $cmdline);
                $cmd = trim($cmdline[0] ?? '');
                if ($cmd !== '' && (str_contains($cmd, 'run.py') || str_contains($cmd, 'collect'))) {
                    @exec("pkill -P {$pid} 2>/dev/null");
                    @exec("kill {$pid} 2>/dev/null");
                } else {
                    error_log("[CollectAdminService] skip kill pid={$pid}, not a collect process: {$cmd}");
                }
            }
        }
        if (!$repo->updateStatus($taskNo, 4, '手动停止')) {
            throw new BusinessException('停止任务失败，状态未更新', 50001);
        }
        return [
            'success' => true,
            'action' => 'stop',
            'task_no' => $taskNo,
        ];
    }

    public function retry(string $taskNo): array
    {
        $repo = new CollectTaskRepository();
        try {
            $task = $repo->findByTaskNoStrict($taskNo);
        } catch (\RuntimeException $e) {
            throw new BusinessException('采集任务服务暂不可用，请稍后重试', 50001);
        }
        if (!$task) {
            throw new BusinessException('采集任务不存在', 40001);
        }
        $status = (int) $task['status'];
        if (!in_array($status, [3, 4], true)) {
            throw new BusinessException('当前任务状态不可重试', 40001);
        }
        if (!$repo->updateStatus($taskNo, 0, '')) {
            throw new BusinessException('重试任务失败，状态未更新', 50001);
        }
        $repo->clearRunnerScript($taskNo);
        return [
            'success' => true,
            'action' => 'retry',
            'task_no' => $taskNo,
        ];
    }
}
