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

            // 并发互斥：两个不同订单在同一用户上同时支付成功会各自 SELECT FOR UPDATE 到空集（gap lock 不保证阻塞并发 INSERT），
            // 导致 user_subscriptions 插入两条活跃行。取 users 行级锁把同一用户的激活流串行化，
            // 让第二个请求能看到第一个刚插入的订阅并走 UPDATE 合并分支。
            $lockStmt = $pdo->prepare('SELECT id FROM users WHERE id = :user_id FOR UPDATE');
            $lockStmt->execute(['user_id' => $userId]);
            if (!$lockStmt->fetch(PDO::FETCH_ASSOC)) {
                if ($ownTransaction) {
                    $pdo->rollBack();
                }
                error_log("[SubscriptionService] user not found: $userId");
                return false;
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
            $isUnlimited = (int) $plan['is_unlimited'];
            $quota = (int) $plan['quota'];
            // 新套餐自己的期限：duration>0 从现在起计算；duration=0 视为永久 → null。
            $expireAt = $duration > 0 ? date('Y-m-d H:i:s', strtotime("+{$duration} days")) : null;

            $stmt = $pdo->prepare('SELECT id, is_unlimited, remain_quota, expire_at FROM user_subscriptions WHERE user_id = :user_id AND (expire_at IS NULL OR expire_at > NOW()) ORDER BY id DESC LIMIT 1 FOR UPDATE');
            $stmt->execute(['user_id' => $userId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // 关键修复：旧订阅 expire_at=null（永久）不应把新的定期套餐也写成永久 —— 那会让续费等于白送。
                // 分支只按"新套餐"的 duration 决定新 expire_at：有期限则从 max(旧到期, now) 起算；永久才设 null。
                if ($duration > 0) {
                    $baseTime = $existing['expire_at'] !== null
                        ? max(strtotime($existing['expire_at']), time())
                        : time();
                    $expireAt = date('Y-m-d H:i:s', strtotime("+{$duration} days", $baseTime));
                } else {
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
