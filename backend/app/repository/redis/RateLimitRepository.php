<?php

namespace app\repository\redis;

use support\adapter\RedisClient;

class RateLimitRepository
{
    // 限流原语：Redis 不可用 / 执行异常时 available=false，调用方据此 fail-closed。
    // 之前用 PHP_INT_MAX 作为哨兵让调用方大小判断，容易被误读为"极高计数"，改为显式结构。
    public function hit(string $key, int $ttl = 60): array
    {
        $redis = RedisClient::connection();
        if (!$redis) {
            error_log("[RateLimitRepository] Redis unavailable, rejecting request (fail-closed)");
            return ['available' => false, 'count' => 0];
        }
        try {
            $fullKey = 'ratelimit:' . $key;
            $lua = "local c = redis.call('INCR',KEYS[1]) if c == 1 then redis.call('EXPIRE',KEYS[1],ARGV[1]) end return c";
            $count = $redis->eval($lua, [$fullKey, $ttl], 1);
            return ['available' => true, 'count' => (int) $count];
        } catch (\Throwable $e) {
            error_log("[RateLimitRepository] hit failed: " . $e->getMessage());
            return ['available' => false, 'count' => 0];
        }
    }
}
