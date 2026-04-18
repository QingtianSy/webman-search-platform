<?php

namespace app\service\payment;

use app\repository\redis\QuotaCacheRepository;
use PDO;
use support\adapter\MySqlClient;

class SubscriptionService
{
    public function activate(int $userId, int $planId, string $orderNo): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $stmt = $pdo->prepare('SELECT id, name, duration, quota, is_unlimited FROM plans WHERE id = :id AND status = 1 LIMIT 1');
            $stmt->execute(['id' => $planId]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$plan) {
                error_log("[SubscriptionService] plan not found: $planId");
                return false;
            }

            $duration = (int) $plan['duration'];
            $expireAt = $duration > 0 ? date('Y-m-d H:i:s', strtotime("+{$duration} days")) : null;
            $quota = (int) $plan['quota'];
            $isUnlimited = (int) $plan['is_unlimited'];

            $stmt = $pdo->prepare('SELECT id, remain_quota, expire_at FROM user_subscriptions WHERE user_id = :user_id ORDER BY id DESC LIMIT 1');
            $stmt->execute(['user_id' => $userId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $remainQuota = $isUnlimited ? 0 : ((int) $existing['remain_quota'] + $quota);
                $stmt = $pdo->prepare('UPDATE user_subscriptions SET name = :name, is_unlimited = :is_unlimited, remain_quota = :remain_quota, expire_at = :expire_at, updated_at = NOW() WHERE id = :id');
                $stmt->execute([
                    'name' => $plan['name'],
                    'is_unlimited' => $isUnlimited,
                    'remain_quota' => $remainQuota,
                    'expire_at' => $expireAt,
                    'id' => $existing['id'],
                ]);
            } else {
                $remainQuota = $isUnlimited ? 0 : $quota;
                $stmt = $pdo->prepare('INSERT INTO user_subscriptions (user_id, name, is_unlimited, remain_quota, used_quota, expire_at, created_at, updated_at) VALUES (:user_id, :name, :is_unlimited, :remain_quota, 0, :expire_at, NOW(), NOW())');
                $stmt->execute([
                    'user_id' => $userId,
                    'name' => $plan['name'],
                    'is_unlimited' => $isUnlimited,
                    'remain_quota' => $remainQuota,
                    'expire_at' => $expireAt,
                ]);
            }

            (new QuotaCacheRepository())->deleteUserQuota($userId);

            return true;
        } catch (\PDOException $e) {
            error_log("[SubscriptionService] activate failed: " . $e->getMessage());
            return false;
        }
    }
}
