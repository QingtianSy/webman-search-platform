<?php

namespace app\repository\redis;

use support\adapter\RedisClient;

// 异步测试任务结果缓存：API 源测试把 Guzzle 请求放到 Timer 协程里跑，
// 结果写在这里，控制器轮询读。避免同步 10s 超时直接吃满 Webman 进程。
// 归属 (user_id / scope) 由投递方写入 setPending()，completeResult() 会原样保留，
// 读取方 (service 层) 必须校验归属再返回给调用者；仅靠 task_id 不可枚举并不构成授权。
class TestTaskCacheRepository
{
    protected const PREFIX = 'test:task';
    protected const TTL = 300; // 5 分钟，前端来得及轮询

    public function newTaskId(): string
    {
        return 'ts_' . bin2hex(random_bytes(12));
    }

    public function setPending(string $taskId, array $meta = []): bool
    {
        return $this->write($taskId, array_merge([
            'status' => 'pending',
            'submitted_at' => date('Y-m-d H:i:s'),
        ], $meta));
    }

    // 完成时只覆盖结果字段，归属元 (user_id/scope/submitted_at) 不能被 result 整包替换掉，
    // 否则读取路径的归属校验会失效。
    public function completeResult(string $taskId, array $result): bool
    {
        $existing = $this->get($taskId);
        if ($existing === null) {
            // pending 已过期或从未写入，不要用 result 反向创建出无归属的缓存项。
            return false;
        }
        $preserved = [];
        foreach (['user_id', 'source_id', 'scope', 'submitted_at'] as $k) {
            if (array_key_exists($k, $existing)) {
                $preserved[$k] = $existing[$k];
            }
        }
        return $this->write($taskId, array_merge($result, $preserved, ['status' => $result['status'] ?? 'done']));
    }

    public function get(string $taskId): ?array
    {
        $redis = RedisClient::connection();
        if (!$redis) {
            return null;
        }
        try {
            $val = $redis->get(RedisClient::key(self::PREFIX, $taskId));
            if ($val === false) {
                return null;
            }
            $data = json_decode((string) $val, true);
            return is_array($data) ? $data : null;
        } catch (\Throwable $e) {
            error_log("[TestTaskCacheRepository] get failed: " . $e->getMessage());
            return null;
        }
    }

    protected function write(string $taskId, array $payload): bool
    {
        $redis = RedisClient::connection();
        if (!$redis) {
            return false;
        }
        try {
            return (bool) $redis->setex(
                RedisClient::key(self::PREFIX, $taskId),
                self::TTL,
                json_encode($payload, JSON_UNESCAPED_UNICODE)
            );
        } catch (\Throwable $e) {
            error_log("[TestTaskCacheRepository] write failed: " . $e->getMessage());
            return false;
        }
    }
}
