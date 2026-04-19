<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class PermissionRepository
{
    public function all(): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->query('SELECT id, name, code, type, created_at, updated_at FROM permissions LIMIT 10000');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[PermissionRepository] all failed: " . $e->getMessage());
            return [];
        }
    }
}
