<?php

namespace app\service\payment;

use app\repository\redis\QuotaCacheRepository;
use PDO;
use support\adapter\MySqlClient;

class SubscriptionService
{
    public function activate(int $userId, int $planId, string $orderNo, ?array $planSnapshot = null): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }
        $ownTransaction = !$pdo->inTransaction();
        try {
            if ($ownTransaction) {
                $pdo->beginTransaction();
            }

            if ($planSnapshot) {
                $plan = $planSnapshot;
            } else {
                $stmt = $pdo->prepare('SELECT id, name, duration, quota, is_unlimited FROM plans WHERE id = :id AND status = 1 LIMIT 1');
                $stmt->execute(['id' => $planId]);
                $plan = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            if (!$plan) {
                if ($ownTransaction) {
                    $pdo->rollBack();
                }
                error_log("[SubscriptionService] plan not found: $planId");
                return false;
            }

            $duration = (int) $plan['duration'];
            $expireAt = $duration > 0 ? date('Y-m-d H:i:s', strtotime("+{$duration} days")) : null;
            $quota = (int) $plan['quota'];
            $isUnlimited = (int) $plan['is_unlimited'];

            $stmt = $pdo->prepare('SELECT id, is_unlimited, remain_quota, expire_at FROM user_subscriptions WHERE user_id = :user_id AND (expire_at IS NULL OR expire_at > NOW()) ORDER BY id DESC LIMIT 1 FOR UPDATE');
            $stmt->execute(['user_id' => $userId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                if ($duration > 0 && $existing['expire_at'] !== null) {
                    $baseTime = max(strtotime($existing['expire_at']), time());
                    $expireAt = date('Y-m-d H:i:s', strtotime("+{$duration} days", $baseTime));
                } elseif ($existing['expire_at'] === null) {
                    $expireAt = null;
                }
                if ($isUnlimited) {
                    $remainQuota = 0;
                } else {
                    $existingRemain = ((int) ($existing['is_unlimited'] ?? 0) === 1) ? 0 : (int) $existing['remain_quota'];
                    $remainQuota = $existingRemain + $quota;
                }
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

            if ($ownTransaction) {
                $pdo->commit();
                // 缓存删除必须在事务提交之后：否则并发读请求在事务期间 miss 后，
                // 会查到提交前的旧快照并把它回填缓存，commit 完成后 DB 是新值、缓存是旧值。
                (new QuotaCacheRepository())->deleteUserQuota($userId);
            }
            // 当调用方拥有事务（如 CallbackService）时，由调用方在自己 commit 后负责失效缓存，
            // 避免在外层事务未提交时就删缓存、触发同样的旧值回填问题。
            return true;
        } catch (\PDOException $e) {
            if ($ownTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("[SubscriptionService] activate failed: " . $e->getMessage());
            return false;
        }
    }
}
