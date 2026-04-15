<?php

namespace support\adapter;

class MongoClient
{
    public static function config(): array
    {
        return config('mongodb', []);
    }

    public static function isConfigured(): bool
    {
        $config = self::config();
        return !empty($config['uri']) && !empty($config['database']);
    }
}
