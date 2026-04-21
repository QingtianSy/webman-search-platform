<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class SubscriptionRepository
{
    public function findCurrentByUserId(int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('SELECT id, user_id, name, is_unlimited, remain_quota, used_quota, expire_at, created_at, updated_at FROM user_subscriptions WHERE user_id = :user_id AND (expire_at IS NULL OR expire_at > NOW()) ORDER BY id DESC LIMIT 1');
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[SubscriptionRepository] findCurrentByUserId failed: " . $e->getMessage());
            return [];
        }
    }

    // 严格版本：DB 故障抛 RuntimeException，避免当前订阅接口把"DB 挂了"伪装成"无订阅"。
    public function findCurrentByUserIdStrict(int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $stmt = $pdo->prepare('SELECT id, user_id, name, is_unlimited, remain_quota, used_quota, expire_at, created_at, updated_at FROM user_subscriptions WHERE user_id = :user_id AND (expire_at IS NULL OR expire_at > NOW()) ORDER BY id DESC LIMIT 1');
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            throw new \RuntimeException('subscription find failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
