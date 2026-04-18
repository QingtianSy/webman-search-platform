<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

/**
 * UserRoleRepository
 */
class UserRoleRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/user_roles.json';
    }

    public function roleIdsByUserId(int $userId): array
    {
        return config('integration.auth_rbac_source', 'mock') === 'real'
            ? $this->roleIdsByUserIdReal($userId)
            : $this->roleIdsByUserIdMock($userId);
    }

    protected function roleIdsByUserIdMock(int $userId): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        $rows = is_array($rows) ? $rows : [];
        $ids = [];
        foreach ($rows as $row) {
            if ((int) ($row['user_id'] ?? 0) === $userId) {
                $ids[] = (int) ($row['role_id'] ?? 0);
            }
        }
        return $ids;
    }

    protected function roleIdsByUserIdReal(int $userId): array
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
                error_log("[UserRoleRepository] roleIdsByUserIdReal failed: " . $e->getMessage());
                return [];
            }
        }
}
