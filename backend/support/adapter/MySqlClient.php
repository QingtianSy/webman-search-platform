<?php

namespace support\adapter;

class MySqlClient
{
    public static function config(): array
    {
        return config('database.connections.mysql', []);
    }

    public static function isConfigured(): bool
    {
        $config = self::config();
        return !empty($config['host']) && !empty($config['database']) && !empty($config['username']);
    }
}
