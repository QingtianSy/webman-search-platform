<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class RoleRepository
{
    public function all(): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->query('SELECT id, name, code, sort, status, created_at, updated_at FROM roles WHERE status = 1');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[RoleRepository] all failed: " . $e->getMessage());
            return [];
        }
    }

    public function findByIds(array $ids): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo || !$ids) {
            return [];
        }
        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT id, name, code, sort, status, created_at, updated_at FROM roles WHERE id IN ($placeholders)");
            $stmt->execute(array_values($ids));
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[RoleRepository] findByIds failed: " . $e->getMessage());
            return [];
        }
    }
}
