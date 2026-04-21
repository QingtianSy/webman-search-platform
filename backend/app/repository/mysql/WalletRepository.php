<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class WalletRepository
{
    public function findByUserId(int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('SELECT id, user_id, balance, frozen_balance, total_recharge, total_consume, created_at, updated_at FROM wallets WHERE user_id = :user_id LIMIT 1');
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[WalletRepository] findByUserId failed: " . $e->getMessage());
            return [];
        }
    }

    // 严格版本：DB 故障抛 RuntimeException，避免钱包接口把"DB 挂了"伪装成"空钱包"。
    public function findByUserIdStrict(int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $stmt = $pdo->prepare('SELECT id, user_id, balance, frozen_balance, total_recharge, total_consume, created_at, updated_at FROM wallets WHERE user_id = :user_id LIMIT 1');
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            throw new \RuntimeException('wallet find failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
