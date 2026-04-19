<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class RolePermissionRepository
{
    public function permissionCodesByRoleIds(array $roleIds): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo || !$roleIds) {
            return [];
        }
        try {
            $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
            $sql = "SELECT DISTINCT p.code FROM role_permission rp INNER JOIN permissions p ON p.id = rp.permission_id AND p.status = 1 INNER JOIN roles r ON r.id = rp.role_id AND r.status = 1 WHERE rp.role_id IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($roleIds));
            return array_values(array_unique(array_filter(array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'code'))));
        } catch (\PDOException $e) {
            error_log("[RolePermissionRepository] permissionCodesByRoleIds failed: " . $e->getMessage());
            return [];
        }
    }

    public function roleCodesByIds(array $roleIds): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo || empty($roleIds)) {
            return [];
        }
        try {
            $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
            $stmt = $pdo->prepare("SELECT code FROM roles WHERE id IN ({$placeholders}) AND status = 1");
            $stmt->execute(array_values($roleIds));
            return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (\PDOException $e) {
            error_log("[RolePermissionRepository] roleCodesByIds failed: " . $e->getMessage());
            return [];
        }
    }

    public function permissionCodesByRoleCodes(array $roleCodes): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo || empty($roleCodes)) {
            return [];
        }
        try {
            $placeholders = implode(',', array_fill(0, count($roleCodes), '?'));
            $sql = "SELECT DISTINCT p.code FROM permissions p "
                . "INNER JOIN role_permission rp ON rp.permission_id = p.id "
                . "INNER JOIN roles r ON r.id = rp.role_id "
                . "WHERE r.code IN ({$placeholders}) AND r.status = 1 AND p.status = 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($roleCodes));
            return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (\PDOException $e) {
            error_log("[RolePermissionRepository] permissionCodesByRoleCodes failed: " . $e->getMessage());
            return [];
        }
    }
}
