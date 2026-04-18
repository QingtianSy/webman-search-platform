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
            $sql = "SELECT p.code FROM role_permission rp INNER JOIN permissions p ON p.id = rp.permission_id WHERE rp.role_id IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($roleIds));
            return array_values(array_unique(array_filter(array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'code'))));
        } catch (\PDOException $e) {
            error_log("[RolePermissionRepository] permissionCodesByRoleIds failed: " . $e->getMessage());
            return [];
        }
    }
}
