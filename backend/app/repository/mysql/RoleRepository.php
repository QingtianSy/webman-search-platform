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
            $stmt = $pdo->prepare("SELECT id, name, code, sort, status, created_at, updated_at FROM roles WHERE id IN ($placeholders) AND status = 1");
            $stmt->execute(array_values($ids));
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[RoleRepository] findByIds failed: " . $e->getMessage());
            return [];
        }
    }

    // 严格版本：DB 故障抛 RuntimeException，避免 RBAC 链路把"DB 挂了"伪装成"无角色"。
    public function findByIdsStrict(array $ids): array
    {
        if (!$ids) {
            return [];
        }
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT id, name, code, sort, status, created_at, updated_at FROM roles WHERE id IN ($placeholders) AND status = 1");
            $stmt->execute(array_values($ids));
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            throw new \RuntimeException('role findByIds failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
