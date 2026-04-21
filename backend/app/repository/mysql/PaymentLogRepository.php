<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class PaymentLogRepository
{
    public function listByUserId(int $userId, int $page = 1, int $pageSize = 20): array
    {
        // 支付流水空列表 = "从没充过值"，与故障相同。故障改为抛出由 service 转 50001。
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM payment_logs WHERE user_id = :user_id');
            $countStmt->execute(['user_id' => $userId]);
            $total = (int) $countStmt->fetchColumn();

            $offset = ($page - 1) * $pageSize;
            $stmt = $pdo->prepare('SELECT id, user_id, order_no, amount, pay_method, status, remark, created_at FROM payment_logs WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
            $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue('limit', $pageSize, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return ['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize];
        } catch (\PDOException $e) {
            error_log("[PaymentLogRepository] listByUserId failed: " . $e->getMessage());
            throw new \RuntimeException('payment log query failed: ' . $e->getMessage(), 0, $e);
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
