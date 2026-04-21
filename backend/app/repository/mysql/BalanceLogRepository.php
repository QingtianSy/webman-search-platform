<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class BalanceLogRepository
{
    public function listByUserId(int $userId, int $page = 1, int $pageSize = 20): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return ['list' => [], 'total' => 0, 'page' => $page, 'page_size' => $pageSize];
        }
        try {
            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM balance_logs WHERE user_id = :user_id');
            $countStmt->execute(['user_id' => $userId]);
            $total = (int) $countStmt->fetchColumn();

            $offset = ($page - 1) * $pageSize;
            $stmt = $pdo->prepare('SELECT id, user_id, type, amount, balance_after, remark, created_at FROM balance_logs WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
            $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue('limit', $pageSize, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return ['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize];
        } catch (\PDOException $e) {
            error_log("[BalanceLogRepository] listByUserId failed: " . $e->getMessage());
            return ['list' => [], 'total' => 0, 'page' => $page, 'page_size' => $pageSize];
        }
    }

    // 严格版本：DB 故障抛 RuntimeException，避免余额流水接口用"空列表"掩盖故障。
    public function listByUserIdStrict(int $userId, int $page = 1, int $pageSize = 20): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM balance_logs WHERE user_id = :user_id');
            $countStmt->execute(['user_id' => $userId]);
            $total = (int) $countStmt->fetchColumn();

            $offset = ($page - 1) * $pageSize;
            $stmt = $pdo->prepare('SELECT id, user_id, type, amount, balance_after, remark, created_at FROM balance_logs WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
            $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue('limit', $pageSize, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return ['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize];
        } catch (\PDOException $e) {
            throw new \RuntimeException('balance log list failed: ' . $e->getMessage(), 0, $e);
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
