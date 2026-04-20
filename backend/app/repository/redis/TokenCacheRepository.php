<?php

namespace app\repository\redis;

use support\adapter\RedisClient;

class TokenCacheRepository
{
    protected const PREFIX = 'token:user';

    public function setUserToken(int $userId, string $token, ?int $ttl = null): bool
    {
        if ($ttl === null) {
            $ttl = (int) (config('jwt.expire', 604800));
        }
        $redis = RedisClient::connection();
        if (!$redis) {
            return false;
        }
        try {
            return $redis->setex(RedisClient::key(self::PREFIX, $userId), $ttl, $token);
        } catch (\Throwable $e) {
            error_log("[TokenCacheRepository] setUserToken failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @return array{connected: bool, token: ?string}
     */
    public function getUserTokenWithStatus(int $userId): array
    {
        $redis = RedisClient::connection();
        if (!$redis) {
            return ['connected' => false, 'token' => null];
        }
        try {
            $val = $redis->get(RedisClient::key(self::PREFIX, $userId));
            return ['connected' => true, 'token' => $val === false ? null : (string) $val];
        } catch (\Throwable $e) {
            error_log("[TokenCacheRepository] getUserToken failed: " . $e->getMessage());
            return ['connected' => false, 'token' => null];
        }
    }

    public function getUserToken(int $userId): ?string
    {
        $redis = RedisClient::connection();
        if (!$redis) {
            return null;
        }
        try {
            $val = $redis->get(RedisClient::key(self::PREFIX, $userId));
            return $val === false ? null : (string) $val;
        } catch (\Throwable $e) {
            error_log("[TokenCacheRepository] getUserToken failed: " . $e->getMessage());
            return null;
        }
    }

    public function deleteToken(int $userId): bool
    {
        $redis = RedisClient::connection();
        if (!$redis) {
            return false;
        }
        try {
            $redis->del(RedisClient::key(self::PREFIX, $userId));
            return true;
        } catch (\Throwable $e) {
            error_log("[TokenCacheRepository] deleteToken failed: " . $e->getMessage());
            return false;
        }
    }
}
