<?php

namespace support\adapter;

class MongoClient
{
    protected static ?\MongoDB\Database $database = null;

    public static function config(): array
    {
        return function_exists('config') ? config('mongodb', []) : [];
    }

    public static function isConfigured(): bool
    {
        $config = self::config();
        return !empty($config['uri']) && !empty($config['database']);
    }

    public static function connection(): ?\MongoDB\Database
    {
        if (self::$database !== null) {
            return self::$database;
        }
        if (!self::isConfigured() || !class_exists(\MongoDB\Client::class)) {
            return null;
        }
        try {
            $cfg = self::config();
            $client = new \MongoDB\Client($cfg['uri']);
            self::$database = $client->selectDatabase($cfg['database']);
            return self::$database;
        } catch (\Throwable $e) {
            error_log("[MongoClient] connection failed: " . $e->getMessage());
            return null;
        }
    }
}
