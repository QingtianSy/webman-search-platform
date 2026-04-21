<?php

namespace app\repository\redis;

use support\adapter\RedisClient;
use support\AppLog;

// 单请求合并鉴权读：把 status / sessions_invalidated_at(ms) / role_codes 压进单 key JSON，
// TTL 短（60s），读一次 Redis 就能顶掉 3 次 DB 往返。
// 权威失效源仍是 users.sessions_invalidated_at —— 所有写该列的路径必须调用 bust() 让缓存立即失效，
// 否则 60s 内旧角色/旧状态会继续生效。
class UserAuthCacheRepository
{
    protected const PREFIX = 'auth:user';
    protected const TTL = 60;

    public function get(int $userId): ?array
    {
        $redis = RedisClient::connection();
        if (!$redis) {
            return null;
        }
        try {
            $val = $redis->get(RedisClient::key(self::PREFIX, $userId));
            if ($val === false) {
                return null;
            }
            $data = json_decode((string) $val, true);
            return is_array($data) ? $data : null;
        } catch (\Throwable $e) {
            AppLog::warn("[UserAuthCacheRepository] get failed: " . $e->getMessage());
            return null;
        }
    }

    public function set(int $userId, array $payload, int $ttl = self::TTL): void
    {
        $redis = RedisClient::connection();
        if (!$redis) {
            return;
        }
        try {
            $redis->setex(
                RedisClient::key(self::PREFIX, $userId),
                $ttl,
                json_encode($payload, JSON_UNESCAPED_UNICODE)
            );
        } catch (\Throwable $e) {
            AppLog::warn("[UserAuthCacheRepository] set failed: " . $e->getMessage());
        }
    }

    public function bust(int $userId): void
    {
        $redis = RedisClient::connection();
        if (!$redis) {
            return;
        }
        try {
            $redis->del(RedisClient::key(self::PREFIX, $userId));
        } catch (\Throwable $e) {
            AppLog::warn("[UserAuthCacheRepository] bust failed: " . $e->getMessage());
        }
    }

    // 严格版：连接可用但 del 失败时抛出，让登出/吊销路径翻 50001 让前端重试，
    // 否则 auth cache 中的旧 invalidated_ms / status / role_codes 会让已登出 token 或停用账号
    // 继续被中间件放行最长 self::TTL（60s）。
    // Redis 完全不可用（连接为 null）时不抛——抛了也补救不了，仍走 TTL 自然兜底。
    public function bustStrict(int $userId): void
    {
        $redis = RedisClient::connection();
        if (!$redis) {
            return;
        }
        try {
            $redis->del(RedisClient::key(self::PREFIX, $userId));
        } catch (\Throwable $e) {
            AppLog::warn("[UserAuthCacheRepository] bustStrict failed: " . $e->getMessage());
            throw new \RuntimeException('[UserAuthCacheRepository] bust failed for user ' . $userId, 0, $e);
        }
    }

    public function bustMany(array $userIds): void
    {
        if (empty($userIds)) {
            return;
        }
        $redis = RedisClient::connection();
        if (!$redis) {
            return;
        }
        try {
            $keys = [];
            foreach ($userIds as $uid) {
                $keys[] = RedisClient::key(self::PREFIX, (int) $uid);
            }
            $redis->del($keys);
        } catch (\Throwable $e) {
            AppLog::warn("[UserAuthCacheRepository] bustMany failed: " . $e->getMessage());
        }
    }
}
