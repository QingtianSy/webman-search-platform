<?php

namespace app\repository\redis;

use support\adapter\RedisClient;

class RateLimitRepository
{
    public function hit(string $key, int $ttl = 60): int
    {
        $redis = RedisClient::connection();
        if (!$redis) {
            error_log("[RateLimitRepository] Redis unavailable, rejecting request (fail-closed)");
            return PHP_INT_MAX;
        }
        try {
            $fullKey = 'ratelimit:' . $key;
            $lua = "local c = redis.call('INCR',KEYS[1]) if c == 1 then redis.call('EXPIRE',KEYS[1],ARGV[1]) end return c";
            $count = $redis->eval($lua, [$fullKey, $ttl], 1);
            return (int) $count;
        } catch (\Throwable $e) {
            error_log("[RateLimitRepository] hit failed: " . $e->getMessage());
            return PHP_INT_MAX;
        }
    }
}
