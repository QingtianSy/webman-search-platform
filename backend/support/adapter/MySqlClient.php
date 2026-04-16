<?php

namespace support\adapter;

use PDO;
use PDOException;

class MySqlClient
{
    public static function config(): array
    {
        $config = function_exists('config') ? config('database.connections.mysql', []) : [];

        if (is_array($config) && !empty($config)) {
            return $config;
        }

        $path = dirname(__DIR__, 2) . '/config/database.php';
        $all = is_file($path) ? require $path : [];

        return $all['connections']['mysql'] ?? [];
    }

    public static function isConfigured(): bool
    {
        $config = self::config();
        return !empty($config['host']) && !empty($config['database']) && !empty($config['username']);
    }

    public static function pdo(): ?PDO
    {
        if (!self::isConfigured()) {
            return null;
        }
        $config = self::config();
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            (int) ($config['port'] ?? 3306),
            $config['database'],
            $config['charset'] ?? 'utf8mb4'
        );
        try {
            return new PDO($dsn, $config['username'], $config['password'] ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException) {
            return null;
        }
    }

    public static function executeSqlFile(string $file): bool
    {
        $pdo = self::pdo();
        if (!$pdo || !is_file($file)) {
            return false;
        }
        $sql = trim((string) file_get_contents($file));
        if ($sql === '') {
            return false;
        }
        $pdo->exec($sql);
        return true;
    }
}
