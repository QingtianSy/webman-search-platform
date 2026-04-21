<?php

namespace app\service\admin;

use app\exception\BusinessException;
use app\repository\mysql\ApiSourceRepository;
use app\repository\redis\TestTaskCacheRepository;
use support\Pagination;
use Workerman\Timer;

class ApiSourceAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['page' => 1, 'page_size' => 20];
        $repo = new ApiSourceRepository();
        try {
            $total = $repo->countAllStrict();
            $list = $repo->findPageStrict((int) $query['page'], (int) $query['page_size']);
        } catch (\RuntimeException $e) {
            // DB 故障直接暴露为 50001，避免把"数据库挂了"翻成"空列表"误导运维。
            throw new BusinessException('接口源服务暂不可用，请稍后重试', 50001);
        }
        return Pagination::format($list, $total, (int) $query['page'], (int) $query['page_size']);
    }

    public function detail(int $id): array
    {
        try {
            return (new ApiSourceRepository())->findByIdStrict($id);
        } catch (\RuntimeException $e) {
            // DB 故障时不再静默返 []，否则控制器会翻成 40004"不存在"。
            throw new BusinessException('接口源服务暂不可用，请稍后重试', 50001);
        }
    }

    public function test(int $id): array
    {
        try {
            $row = (new ApiSourceRepository())->test($id);
        } catch (\RuntimeException $e) {
            // 仓库层 findByIdStrict 在 DB 不可用时抛 RuntimeException；此路径不是 HTTP 测试失败，是基础设施故障。
            throw new BusinessException('接口源服务暂不可用，请稍后重试', 50001);
        }
        if (empty($row)) {
            throw new BusinessException('接口源不存在', 40004);
        }
        return [
            'success' => ($row['status'] ?? '') === 'success',
            'action' => 'test',
            'id' => $id,
            'data' => $row,
        ];
    }

    // 异步测试：投递 + 轮询，避免 Guzzle 超时吃满 Webman 进程。
    public function submitTest(int $id): array
    {
        $cache = new TestTaskCacheRepository();
        $taskId = $cache->newTaskId();
        if (!$cache->setPending($taskId, ['source_id' => $id, 'scope' => 'admin'])) {
            throw new BusinessException('测试服务暂不可用，请稍后重试', 50001);
        }

        Timer::add(0.001, function () use ($id, $taskId, $cache) {
            try {
                $result = (new ApiSourceRepository())->test($id);
            } catch (\Throwable $e) {
                error_log("[ApiSourceAdminService] async test threw: " . $e->getMessage());
                $result = [
                    'id' => $id,
                    'status' => 'error',
                    'message' => '测试任务异常，详情见服务端日志',
                    'tested_at' => date('Y-m-d H:i:s'),
                ];
            }
            $cache->completeResult($taskId, $result);
        }, [], false);

        return ['task_id' => $taskId, 'status' => 'pending'];
    }

    // 仅 scope='admin' 的任务可被 admin 读取；防止普通用户拿到泄露的 task_id 后越权读到管理侧源的探测结果。
    public function getTestResult(string $taskId): array
    {
        $row = (new TestTaskCacheRepository())->get($taskId);
        if ($row === null) {
            throw new BusinessException('测试任务不存在或已过期', 40004);
        }
        if (($row['scope'] ?? null) !== 'admin') {
            throw new BusinessException('测试任务不存在或已过期', 40004);
        }
        unset($row['user_id'], $row['scope']);
        return $row;
    }
}
