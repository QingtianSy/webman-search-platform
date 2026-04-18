<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class SystemConfigRepository
{
    public function all(): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->query('SELECT id, group_code, config_key, config_value, value_type, status, created_at, updated_at FROM system_configs ORDER BY id DESC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[SystemConfigRepository] all failed: " . $e->getMessage());
            return [];
        }
    }

    public function updateByKey(string $key, string $value): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('UPDATE system_configs SET config_value = :config_value, updated_at = NOW() WHERE config_key = :config_key');
            $stmt->execute([
                'config_key' => $key,
                'config_value' => $value,
            ]);
            $stmt = $pdo->prepare('SELECT id, group_code, config_key, config_value, value_type, status, created_at, updated_at FROM system_configs WHERE config_key = :config_key LIMIT 1');
            $stmt->execute(['config_key' => $key]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[SystemConfigRepository] updateByKey failed: " . $e->getMessage());
            return [];
        }
    }
}
