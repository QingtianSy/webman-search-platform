<?php

namespace app\repository\redis;

use support\adapter\RedisClient;

class QuotaCacheRepository
{
    protected const PREFIX = 'quota:user';

    public function getUserQuota(int $userId): int
    {
        $redis = RedisClient::connection();
        if (!$redis) {
            return -1;
        }
        try {
            $val = $redis->get(RedisClient::key(self::PREFIX, $userId));
            return $val === false ? -1 : (int) $val;
        } catch (\Throwable $e) {
            error_log("[QuotaCacheRepository] getUserQuota failed: " . $e->getMessage());
            return -1;
        }
    }

    public function setUserQuota(int $userId, int $quota, int $ttl = 86400): bool
    {
        $redis = RedisClient::connection();
        if (!$redis) {
            return false;
        }
        try {
            return $redis->setex(RedisClient::key(self::PREFIX, $userId), $ttl, $quota);
        } catch (\Throwable $e) {
            error_log("[QuotaCacheRepository] setUserQuota failed: " . $e->getMessage());
            return false;
        }
    }

    public function decrementQuota(int $userId): int
    {
        $redis = RedisClient::connection();
        if (!$redis) {
            return -1;
        }
        try {
            $key = RedisClient::key(self::PREFIX, $userId);
            $val = $redis->decr($key);
            return $val !== false ? (int) $val : -1;
        } catch (\Throwable $e) {
            error_log("[QuotaCacheRepository] decrementQuota failed: " . $e->getMessage());
            return -1;
        }
    }

    public function deleteUserQuota(int $userId): bool
    {
        $redis = RedisClient::connection();
        if (!$redis) {
            return false;
        }
        try {
            $redis->del(RedisClient::key(self::PREFIX, $userId));
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
