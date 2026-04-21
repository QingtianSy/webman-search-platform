<?php

namespace app\service\quota;

use app\exception\BusinessException;
use app\repository\redis\QuotaCacheRepository;
use PDO;
use support\adapter\MySqlClient;
use support\AppLog;

class QuotaService
{
    // 额度链路严格化背景：
    //   旧实现在 MySQL/Redis 抖动时统一返回 0 / false，上层 SearchService 会把它翻译成"额度不足 40006"。
    //   效果：基础设施故障 → 有余额的用户被挡在门外，既误导用户又让运维看不到 DB 故障告警。
    //   现在所有 DB/Redis 不可用都抛 BusinessException(50001) 暴露出去；consume 的"额度用完"语义用返回 false 保留。
    //   Redis getUserQuota 返回 -1（未缓存）仅代表 miss，不抛异常，回退 DB。

    public function getUserQuota(int $userId): int
    {
        $cache = new QuotaCacheRepository();
        $cached = $cache->getUserQuota($userId);
        if ($cached >= 0) {
            return $cached;
        }

        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new BusinessException('额度服务暂不可用，请稍后重试', 50001);
        }
        try {
            $stmt = $pdo->prepare('SELECT is_unlimited, remain_quota FROM user_subscriptions WHERE user_id = :user_id AND (expire_at IS NULL OR expire_at > NOW()) ORDER BY id DESC LIMIT 1');
            $stmt->execute(['user_id' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                // 无有效订阅 → 真实"额度 0"，不是故障。缓存也写 0 避免反复打 DB。
                $cache->setUserQuota($userId, 0);
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
            AppLog::warn("[QuotaService] getUserQuota failed: " . $e->getMessage());
            throw new BusinessException('额度服务暂不可用，请稍后重试', 50001);
        }
    }

    public function getUserQuotaDetail(int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            // open/quota/detail 与 user/plan/current 等都会调到这里；DB 挂时显示 0 额度会诱导用户退订/冲值，所以 50001。
            throw new BusinessException('额度服务暂不可用，请稍后重试', 50001);
        }
        try {
            $stmt = $pdo->prepare('SELECT is_unlimited, remain_quota FROM user_subscriptions WHERE user_id = :user_id AND (expire_at IS NULL OR expire_at > NOW()) ORDER BY id DESC LIMIT 1');
            $stmt->execute(['user_id' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return ['remain_quota' => 0, 'is_unlimited' => false];
            }
            return [
                'remain_quota' => (int) $row['remain_quota'],
                'is_unlimited' => (int) $row['is_unlimited'] === 1,
            ];
        } catch (\PDOException $e) {
            AppLog::warn("[QuotaService] getUserQuotaDetail failed: " . $e->getMessage());
            throw new BusinessException('额度服务暂不可用，请稍后重试', 50001);
        }
    }

    public function refund(int $userId, int $amount = 1): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }

        $cache = new QuotaCacheRepository();
        try {
            $check = $pdo->prepare('SELECT is_unlimited FROM user_subscriptions WHERE user_id = :user_id AND (expire_at IS NULL OR expire_at > NOW()) ORDER BY id DESC LIMIT 1');
            $check->execute(['user_id' => $userId]);
            $row = $check->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return false;
            }
            if ((int) $row['is_unlimited'] === 1) {
                return true;
            }
            $stmt = $pdo->prepare('UPDATE user_subscriptions SET remain_quota = remain_quota + :amount, used_quota = GREATEST(used_quota - :amount2, 0), updated_at = NOW() WHERE user_id = :user_id AND (expire_at IS NULL OR expire_at > NOW()) AND is_unlimited = 0 AND used_quota >= :min_used ORDER BY id DESC LIMIT 1');
            $ok = $stmt->execute([
                'amount' => $amount,
                'amount2' => $amount,
                'user_id' => $userId,
                'min_used' => $amount,
            ]);
            $cache->deleteUserQuota($userId);
            return $ok && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            // refund 是补偿路径（搜索零命中退额度），DB 挂时失败不该再把调用者 SearchService 抛崩溃。
            // 返回 false 让调用方记录 warning 即可，避免把一次成功的搜索因为退款失败翻成 500。
            AppLog::warn("[QuotaService] refund failed: " . $e->getMessage());
            $cache->deleteUserQuota($userId);
            return false;
        }
    }

    public function consume(int $userId, int $amount = 1): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new BusinessException('额度服务暂不可用，请稍后重试', 50001);
        }

        $cache = new QuotaCacheRepository();
        try {
            $check = $pdo->prepare('SELECT is_unlimited FROM user_subscriptions WHERE user_id = :user_id AND (expire_at IS NULL OR expire_at > NOW()) ORDER BY id DESC LIMIT 1');
            $check->execute(['user_id' => $userId]);
            $row = $check->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $cache->deleteUserQuota($userId);
                return false;
            }
            if ((int) $row['is_unlimited'] === 1) {
                return true;
            }
            $stmt = $pdo->prepare('UPDATE user_subscriptions SET remain_quota = remain_quota - :amount, used_quota = used_quota + :amount2, updated_at = NOW() WHERE user_id = :user_id AND (expire_at IS NULL OR expire_at > NOW()) AND is_unlimited = 0 AND remain_quota >= :check ORDER BY id DESC LIMIT 1');
            $ok = $stmt->execute([
                'amount' => $amount,
                'amount2' => $amount,
                'user_id' => $userId,
                'check' => $amount,
            ]);
            if (!$ok || $stmt->rowCount() === 0) {
                // 0 行被更新：要么 remain_quota 不够（真"额度不足"），要么订阅过期。语义用返回 false 表达。
                $cache->deleteUserQuota($userId);
                return false;
            }
            $cache->deleteUserQuota($userId);
            return true;
        } catch (\PDOException $e) {
            AppLog::warn("[QuotaService] consume failed: " . $e->getMessage());
            $cache->deleteUserQuota($userId);
            throw new BusinessException('额度服务暂不可用，请稍后重试', 50001);
        }
    }
}
