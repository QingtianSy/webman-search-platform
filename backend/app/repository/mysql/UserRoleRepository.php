<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class UserRoleRepository
{
    public function roleIdsByUserId(int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('SELECT role_id FROM user_role WHERE user_id = :user_id');
            $stmt->execute(['user_id' => $userId]);
            return array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'role_id'));
        } catch (\PDOException $e) {
            error_log("[UserRoleRepository] roleIdsByUserId failed: " . $e->getMessage());
            return [];
        }
    }

    public function userIdsByRoleId(int $roleId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('SELECT user_id FROM user_role WHERE role_id = :role_id');
            $stmt->execute(['role_id' => $roleId]);
            return array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'user_id'));
        } catch (\PDOException $e) {
            error_log("[UserRoleRepository] userIdsByRoleId failed: " . $e->getMessage());
            return [];
        }
    }

    // 严格版本：DB 故障抛 RuntimeException，避免 RBAC 链路把"DB 挂了"伪装成"该用户没角色 → 权限/菜单为空"。
    public function roleIdsByUserIdStrict(int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $stmt = $pdo->prepare('SELECT role_id FROM user_role WHERE user_id = :user_id');
            $stmt->execute(['user_id' => $userId]);
            return array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'role_id'));
        } catch (\PDOException $e) {
            throw new \RuntimeException('user role list failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
