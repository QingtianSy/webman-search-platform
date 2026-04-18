<?php

namespace support\adapter;

class MongoClient
{
    public static function config(): array
    {
        return function_exists('config') ? config('mongodb', []) : [];
    }

    public static function isConfigured(): bool
    {
        $config = self::config();
        return !empty($config['uri']) && !empty($config['database']);
    }
}
