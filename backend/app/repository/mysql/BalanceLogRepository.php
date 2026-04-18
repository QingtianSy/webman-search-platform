<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class BalanceLogRepository
{
    public function listByUserId(int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('SELECT id, user_id, type, amount, balance_after, remark, created_at FROM balance_logs WHERE user_id = :user_id ORDER BY created_at DESC');
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[BalanceLogRepository] listByUserId failed: " . $e->getMessage());
            return [];
        }
    }

    public function create(array $data): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $stmt = $pdo->prepare('INSERT INTO balance_logs (user_id, type, amount, balance_after, remark, created_at) VALUES (:user_id, :type, :amount, :balance_after, :remark, NOW())');
            return $stmt->execute([
                'user_id' => $data['user_id'] ?? 0,
                'type' => $data['type'] ?? '',
                'amount' => $data['amount'] ?? 0,
                'balance_after' => $data['balance_after'] ?? 0,
                'remark' => $data['remark'] ?? '',
            ]);
        } catch (\PDOException $e) {
            error_log("[BalanceLogRepository] create failed: " . $e->getMessage());
            return false;
        }
    }
}
