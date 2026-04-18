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
            error_log("[SubscriptionRepository] Mock file not found: {$this->file}");
            return [];
        }
        
        $content = @file_get_contents($this->file);
        if ($content === false) {
            error_log("[SubscriptionRepository] Failed to read mock file: {$this->file}");
            return [];
        }
        
        $rows = json_decode($content, true);
        if (!is_array($rows)) {
            error_log("[SubscriptionRepository] Invalid JSON in mock file: {$this->file}");
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
            error_log("[SubscriptionRepository] Database connection failed");
            return [];
        }
        
        try {
            $stmt = $pdo->prepare('SELECT id, user_id, name, is_unlimited, remain_quota, used_quota, expire_at, created_at, updated_at FROM user_subscriptions WHERE user_id = :user_id ORDER BY id DESC LIMIT 1');
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[SubscriptionRepository] Query failed: " . $e->getMessage());
            return [];
        }
    }
}
