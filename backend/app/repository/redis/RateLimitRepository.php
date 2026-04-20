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
            $count = $redis->incr($fullKey);
            if ($count === 1) {
                $redis->expire($fullKey, $ttl);
            }
            return (int) $count;
        } catch (\Throwable $e) {
            error_log("[RateLimitRepository] hit failed: " . $e->getMessage());
            return PHP_INT_MAX;
        }
    }
}
