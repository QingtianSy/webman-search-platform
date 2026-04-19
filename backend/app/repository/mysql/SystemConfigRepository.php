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
            $stmt = $pdo->query('SELECT id, group_code, config_key, config_value, value_type, status, created_at, updated_at FROM system_configs ORDER BY id DESC LIMIT 10000');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[SystemConfigRepository] all failed: " . $e->getMessage());
            return [];
        }
    }

    public function countAll(): int
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return 0;
        }
        try {
            return (int) $pdo->query('SELECT COUNT(*) FROM system_configs')->fetchColumn();
        } catch (\PDOException $e) {
            error_log("[SystemConfigRepository] countAll failed: " . $e->getMessage());
            return 0;
        }
    }

    public function findPage(int $page, int $pageSize): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $offset = ($page - 1) * $pageSize;
            $stmt = $pdo->prepare('SELECT id, group_code, config_key, config_value, value_type, status, created_at, updated_at FROM system_configs ORDER BY id DESC LIMIT :limit OFFSET :offset');
            $stmt->bindValue('limit', $pageSize, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[SystemConfigRepository] findPage failed: " . $e->getMessage());
            return [];
        }
    }

    public function getByGroup(string $groupCode): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('SELECT id, group_code, config_key, config_value, value_type, status, created_at, updated_at FROM system_configs WHERE group_code = :group_code ORDER BY id ASC');
            $stmt->execute(['group_code' => $groupCode]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[SystemConfigRepository] getByGroup failed: " . $e->getMessage());
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
