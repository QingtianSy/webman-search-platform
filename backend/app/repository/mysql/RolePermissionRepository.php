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

    // 严格版本：DB 故障抛 RuntimeException，避免 RBAC 把"DB 挂了"伪装成"此用户无任何权限 → 40003"。
    public function permissionCodesByRoleIdsStrict(array $roleIds): array
    {
        if (!$roleIds) {
            return [];
        }
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
            $sql = "SELECT DISTINCT p.code FROM role_permission rp INNER JOIN permissions p ON p.id = rp.permission_id AND p.status = 1 INNER JOIN roles r ON r.id = rp.role_id AND r.status = 1 WHERE rp.role_id IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($roleIds));
            return array_values(array_unique(array_filter(array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'code'))));
        } catch (\PDOException $e) {
            throw new \RuntimeException('role permission list failed: ' . $e->getMessage(), 0, $e);
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

    // 严格版本：DB 故障抛 RuntimeException，避免中间件缓存 miss 时把"DB 挂了"翻成"此用户无角色 → 40003"。
    public function roleCodesByIdsStrict(array $roleIds): array
    {
        if (empty($roleIds)) {
            return [];
        }
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
            $stmt = $pdo->prepare("SELECT code FROM roles WHERE id IN ({$placeholders}) AND status = 1");
            $stmt->execute(array_values($roleIds));
            return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (\PDOException $e) {
            throw new \RuntimeException('role codes lookup failed: ' . $e->getMessage(), 0, $e);
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

    // 严格版本：DB 故障抛 RuntimeException，避免中间件在权限缓存 miss 时把"DB 挂了"翻成 40003。
    public function permissionCodesByRoleCodesStrict(array $roleCodes): array
    {
        if (empty($roleCodes)) {
            return [];
        }
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
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
            throw new \RuntimeException('permission codes lookup failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
