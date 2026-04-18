<?php

namespace support\adapter;

use Redis;

class RedisClient
{
    public static function config(): array
    {
        return function_exists('config') ? config('redis.default', []) : [];
    }

    public static function isConfigured(): bool
    {
        $config = self::config();
        return !empty($config['host']) && !empty($config['port']);
    }

    public static function connection(): ?Redis
    {
        if (!self::isConfigured() || !class_exists(Redis::class)) {
            return null;
        }
        try {
            $cfg = self::config();
            $redis = new Redis();
            $redis->connect((string)$cfg['host'], (int)$cfg['port'], 2.0);
            if (!empty($cfg['password'])) {
                $redis->auth((string)$cfg['password']);
            }
            if (isset($cfg['database'])) {
                $redis->select((int)$cfg['database']);
            }
            return $redis;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function key(string $prefix, string|int $id): string
    {
        return $prefix . ':' . $id;
    }
}
