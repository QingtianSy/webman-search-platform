<?php

namespace app\service\quota;

use app\repository\redis\QuotaCacheRepository;
use PDO;
use support\adapter\MySqlClient;

class QuotaService
{
    public function getUserQuota(int $userId): int
    {
        $cache = new QuotaCacheRepository();
        $cached = $cache->getUserQuota($userId);
        if ($cached >= 0) {
            return $cached;
        }

        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return 0;
        }
        try {
            $stmt = $pdo->prepare('SELECT is_unlimited, remain_quota FROM user_subscriptions WHERE user_id = :user_id AND (expire_at IS NULL OR expire_at > NOW()) ORDER BY id DESC LIMIT 1');
            $stmt->execute(['user_id' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return 0;
            }
            if ((int) $row['is_unlimited'] === 1) {
                $cache->setUserQuota($userId, 999999);
                return 999999;
            }
            $quota = (int) $row['remain_quota'];
            $cache->setUserQuota($userId, $quota);
            return $quota;
        } catch (\PDOException $e) {
            error_log("[QuotaService] getUserQuota failed: " . $e->getMessage());
            return 0;
        }
    }

    public function consume(int $userId, int $amount = 1): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $cache = new QuotaCacheRepository();
        $remaining = $cache->decrementQuota($userId);

        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return $remaining >= 0;
        }
        try {
            $stmt = $pdo->prepare('UPDATE user_subscriptions SET remain_quota = remain_quota - :amount, used_quota = used_quota + :amount2, updated_at = NOW() WHERE user_id = :user_id AND remain_quota >= :check AND (expire_at IS NULL OR expire_at > NOW()) ORDER BY id DESC LIMIT 1');
            $ok = $stmt->execute([
                'amount' => $amount,
                'amount2' => $amount,
                'user_id' => $userId,
                'check' => $amount,
            ]);
            if ($ok && $stmt->rowCount() === 0) {
                $cache->deleteUserQuota($userId);
                return false;
            }
            return $ok;
        } catch (\PDOException $e) {
            error_log("[QuotaService] consume failed: " . $e->getMessage());
            $cache->deleteUserQuota($userId);
            return false;
        }
    }
}
