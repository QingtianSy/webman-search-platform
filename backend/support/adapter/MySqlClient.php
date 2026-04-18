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
        $c = self::config();
        return !empty($c['host']) && !empty($c['database']) && !empty($c['username']);
    }

    public static function pdo(): ?PDO
    {
        $c = self::config();
        try {
            return new PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $c['host'], $c['port'], $c['database'], $c['charset'] ?? 'utf8mb4'),
                $c['username'] ?? '',
                $c['password'] ?? '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            error_log(sprintf(
                "[MySqlClient] PDO connection failed: %s (host=%s, database=%s, user=%s)",
                $e->getMessage(),
                $c['host'] ?? 'N/A',
                $c['database'] ?? 'N/A',
                $c['username'] ?? 'N/A'
            ));
            return null;
        }
    }

    public static function executeSqlFile(string $file): bool
    {
        $pdo = self::pdo();
        if (!$pdo || !is_file($file)) {
            return false;
        }
        $sql = file_get_contents($file);
        return $pdo->exec($sql) !== false || $sql !== false;
    }
}
