<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class SubscriptionRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/subscriptions.json';
    }

    public function findCurrentByUserId(int $userId): array
    {
        return config('integration.user_center_source', 'mock') === 'real'
            ? $this->findCurrentByUserIdReal($userId)
            : $this->findCurrentByUserIdMock($userId);
    }

    protected function findCurrentByUserIdMock(int $userId): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        if (!is_array($rows)) {
            return [];
        }
        foreach ($rows as $row) {
            if ((int) ($row['user_id'] ?? 0) === $userId) {
                return $row;
            }
        }
        return [];
    }

    protected function findCurrentByUserIdReal(int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        $stmt = $pdo->prepare('SELECT id, user_id, name, is_unlimited, remain_quota, used_quota, expire_at, created_at, updated_at FROM user_subscriptions WHERE user_id = :user_id ORDER BY id DESC LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}
