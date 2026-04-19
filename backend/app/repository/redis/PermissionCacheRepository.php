<?php

namespace app\repository\redis;

use support\adapter\RedisClient;

class PermissionCacheRepository
{
    protected const PREFIX = 'perm:roles';
    protected const TTL = 300;

    public function getPermissions(array $roleCodes): ?array
    {
        $redis = RedisClient::connection();
        if (!$redis) {
            return null;
        }
        try {
            $key = $this->buildKey($roleCodes);
            $val = $redis->get($key);
            return $val === false ? null : json_decode($val, true);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function setPermissions(array $roleCodes, array $permissions): void
    {
        $redis = RedisClient::connection();
        if (!$redis) {
            return;
        }
        try {
            $redis->setex($this->buildKey($roleCodes), self::TTL, json_encode($permissions));
        } catch (\Throwable $e) {
            // ignore
        }
    }

    protected function buildKey(array $roleCodes): string
    {
        sort($roleCodes);
        return RedisClient::key(self::PREFIX, implode(',', $roleCodes));
    }
}
