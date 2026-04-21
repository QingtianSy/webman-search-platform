<?php

namespace app\service\user;

use app\exception\BusinessException;
use app\repository\mysql\UserApiSourceRepository;
use app\repository\redis\TestTaskCacheRepository;
use Workerman\Timer;

class ApiSourceService
{
    public function getList(int $userId, array $query = []): array
    {
        try {
            return (new UserApiSourceRepository())->findByUserIdStrict($userId, $query);
        } catch (\RuntimeException $e) {
            // 之前返 {list:[], total:0}，前端以为"用户没配过源"，可能诱导用户重复新建已有接口。
            throw new BusinessException('数据源暂不可用，请稍后重试', 50001);
        }
    }

    public function detail(int $userId, int $id): array
    {
        try {
            return (new UserApiSourceRepository())->findByIdStrict($userId, $id);
        } catch (\Throwable $e) {
            error_log("[ApiSourceService] detail failed: " . $e->getMessage());
            throw new BusinessException('数据源暂不可用，请稍后重试', 50001);
        }
    }

    public function create(int $userId, array $data): array
    {
        $repo = new UserApiSourceRepository();
        try {
            $id = $repo->createStrict($userId, $data);
            return $repo->findByIdStrict($userId, $id);
        } catch (\Throwable $e) {
            error_log("[ApiSourceService] create failed: " . $e->getMessage());
            throw new BusinessException('接口源创建失败，请稍后重试', 50001);
        }
    }

    public function update(int $userId, int $id, array $data): array
    {
        $repo = new UserApiSourceRepository();
        // 故障（PDO 不可用/异常）→ 50001；记录不存在 → 40004。不再用"不存在或更新失败"混淆两类。
        try {
            $ok = $repo->updateStrict($userId, $id, $data);
        } catch (\Throwable $e) {
            error_log("[ApiSourceService] update failed: " . $e->getMessage());
            throw new BusinessException('接口源更新失败，请稍后重试', 50001);
        }
        if (!$ok) {
            throw new BusinessException('接口源不存在', 40004);
        }
        try {
            return $repo->findByIdStrict($userId, $id);
        } catch (\Throwable $e) {
            // 已更新成功但回读失败：不把它掩盖成 40004，明确报 50001。
            error_log("[ApiSourceService] update refetch failed: " . $e->getMessage());
            throw new BusinessException('接口源已更新，但读取回显失败', 50001);
        }
    }

    public function delete(int $userId, int $id): bool
    {
        try {
            $ok = (new UserApiSourceRepository())->deleteStrict($userId, $id);
        } catch (\Throwable $e) {
            error_log("[ApiSourceService] delete failed: " . $e->getMessage());
            throw new BusinessException('接口源删除失败，请稍后重试', 50001);
        }
        if (!$ok) {
            throw new BusinessException('接口源不存在', 40004);
        }
        return true;
    }

    public function test(int $userId, int $id): array
    {
        $result = (new UserApiSourceRepository())->test($userId, $id);
        // repository 已经吸收 RuntimeException → infra=true；service 层翻译成 50001，
        // 不要把基础设施故障当成业务错误让前端看到"测试失败"自以为是配置问题。
        if (!empty($result['infra'])) {
            throw new BusinessException('数据源暂不可用，请稍后重试', 50001);
        }
        return $result;
    }

    // 异步测试：投递任务 + 轮询结果。避免 Guzzle 10s 超时直接阻塞 Webman 进程。
    // 前端流程：POST /api-source/test-submit → {task_id, status:'pending'}
    //          GET  /api-source/test-result?task_id=... 轮询直到 status ≠ pending。
    public function submitTest(int $userId, int $id): array
    {
        $cache = new TestTaskCacheRepository();
        $taskId = $cache->newTaskId();
        if (!$cache->setPending($taskId, ['user_id' => $userId, 'source_id' => $id, 'scope' => 'user'])) {
            throw new BusinessException('测试服务暂不可用，请稍后重试', 50001);
        }

        Timer::add(0.001, function () use ($userId, $id, $taskId, $cache) {
            try {
                $result = (new UserApiSourceRepository())->test($userId, $id);
            } catch (\Throwable $e) {
                error_log("[ApiSourceService] async test threw: " . $e->getMessage());
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

    // 读取测试结果必须校验归属：task_id 为 24 hex 字节不可枚举，但一旦泄露
    // (日志/浏览器历史/转发链) 别的已登录用户就能读到别人源的响应。
    public function getTestResult(int $userId, string $taskId): array
    {
        $row = (new TestTaskCacheRepository())->get($taskId);
        if ($row === null) {
            throw new BusinessException('测试任务不存在或已过期', 40004);
        }
        $scope = $row['scope'] ?? null;
        $ownerId = (int) ($row['user_id'] ?? 0);
        if ($scope !== 'user' || $ownerId !== $userId) {
            // 不暴露"存在但无权访问"与"不存在"的差别，统一 404。
            throw new BusinessException('测试任务不存在或已过期', 40004);
        }
        // 返回前剥掉归属元，避免把 user_id/scope 回显给调用方。
        unset($row['user_id'], $row['scope']);
        return $row;
    }
}
