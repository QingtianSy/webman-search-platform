<?php

namespace support\adapter;

use Redis;

class RedisClient
{
    protected static ?Redis $redis = null;

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
        if (self::$redis !== null) {
            try {
                self::$redis->ping();
                return self::$redis;
            } catch (\Throwable) {
                self::$redis = null;
            }
        }
        if (!self::isConfigured() || !class_exists(Redis::class)) {
            return null;
        }
        try {
            $cfg = self::config();
            $redis = new Redis();
            $redis->pconnect((string)$cfg['host'], (int)$cfg['port'], 2.0);
            if (!empty($cfg['password'])) {
                $redis->auth((string)$cfg['password']);
            }
            if (isset($cfg['database'])) {
                $redis->select((int)$cfg['database']);
            }
            self::$redis = $redis;
            return self::$redis;
        } catch (\Throwable $e) {
            error_log("[RedisClient] connection failed: " . $e->getMessage());
            self::$redis = null;
            return null;
        }
    }

    public static function key(string $prefix, string|int $id): string
    {
        return $prefix . ':' . $id;
    }
}
