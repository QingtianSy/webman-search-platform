<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class PaymentLogRepository
{
    public function listByUserId(int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('SELECT id, user_id, order_no, amount, pay_method, status, remark, created_at FROM payment_logs WHERE user_id = :user_id ORDER BY created_at DESC');
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[PaymentLogRepository] listByUserId failed: " . $e->getMessage());
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
            $stmt = $pdo->prepare('INSERT INTO payment_logs (user_id, order_no, amount, pay_method, status, remark, created_at) VALUES (:user_id, :order_no, :amount, :pay_method, :status, :remark, NOW())');
            return $stmt->execute([
                'user_id' => $data['user_id'] ?? 0,
                'order_no' => $data['order_no'] ?? '',
                'amount' => $data['amount'] ?? 0,
                'pay_method' => $data['pay_method'] ?? '',
                'status' => $data['status'] ?? 1,
                'remark' => $data['remark'] ?? '',
            ]);
        } catch (\PDOException $e) {
            error_log("[PaymentLogRepository] create failed: " . $e->getMessage());
            return false;
        }
    }
}
