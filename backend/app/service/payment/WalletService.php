<?php

namespace app\service\payment;

use app\repository\mysql\BalanceLogRepository;
use PDO;
use support\adapter\MySqlClient;

class WalletService
{
    public function recharge(int $userId, string $amount, string $orderNo): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT id, balance FROM wallets WHERE user_id = :user_id FOR UPDATE');
            $stmt->execute(['user_id' => $userId]);
            $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($wallet) {
                $newBalance = bcadd($wallet['balance'], $amount, 2);
                $stmt = $pdo->prepare('UPDATE wallets SET balance = :balance, total_recharge = total_recharge + :amount, updated_at = NOW() WHERE id = :id');
                $stmt->execute(['balance' => $newBalance, 'amount' => $amount, 'id' => $wallet['id']]);
            } else {
                $newBalance = $amount;
                $stmt = $pdo->prepare('INSERT INTO wallets (user_id, balance, frozen_balance, total_recharge, total_consume, created_at, updated_at) VALUES (:user_id, :balance, 0, :total_recharge, 0, NOW(), NOW())');
                $stmt->execute(['user_id' => $userId, 'balance' => $amount, 'total_recharge' => $amount]);
            }

            $pdo->commit();

            (new BalanceLogRepository())->create([
                'user_id' => $userId,
                'type' => 'recharge',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'remark' => '在线充值 ' . $orderNo,
            ]);

            return true;
        } catch (\PDOException $e) {
            $pdo->rollBack();
            error_log("[WalletService] recharge failed: " . $e->getMessage());
            return false;
        }
    }
}
