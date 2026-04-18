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
}
