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
}
