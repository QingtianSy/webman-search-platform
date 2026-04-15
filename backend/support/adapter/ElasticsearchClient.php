<?php

namespace support\adapter;

class ElasticsearchClient
{
    public static function config(): array
    {
        return config('elasticsearch', []);
    }

    public static function isConfigured(): bool
    {
        $config = self::config();
        return !empty($config['host']);
    }
}
