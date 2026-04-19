<?php

namespace support\adapter;

class MongoClient
{
    protected static ?\MongoDB\Database $database = null;
    protected static int $lastPingAt = 0;

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
            if (time() - self::$lastPingAt < 30) {
                return self::$database;
            }
            try {
                self::$database->command(['ping' => 1]);
                self::$lastPingAt = time();
                return self::$database;
            } catch (\Throwable) {
                self::$database = null;
            }
        }
        if (!self::isConfigured() || !class_exists(\MongoDB\Client::class)) {
            return null;
        }
        try {
            $cfg = self::config();
            $client = new \MongoDB\Client($cfg['uri'], [], [
                'serverSelectionTimeoutMS' => 5000,
                'connectTimeoutMS' => 5000,
                'socketTimeoutMS' => 30000,
            ]);
            self::$database = $client->selectDatabase($cfg['database']);
            self::$lastPingAt = time();
            return self::$database;
        } catch (\Throwable $e) {
            error_log("[MongoClient] connection failed: " . $e->getMessage());
            self::$database = null;
            return null;
        }
    }
}
