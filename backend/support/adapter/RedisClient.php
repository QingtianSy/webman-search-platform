<?php

namespace support\adapter;

class RedisClient
{
    public static function config(): array
    {
        return config('redis.default', []);
    }

    public static function isConfigured(): bool
    {
        $config = self::config();
        return !empty($config['host']) && !empty($config['port']);
    }

    public static function key(string $prefix, string|int $id): string
    {
        return $prefix . ':' . $id;
    }
}
