<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class LoginLogRepository
{
    public function listByUserId(int $userId, int $page = 1, int $pageSize = 20): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return ['list' => [], 'total' => 0, 'page' => $page, 'page_size' => $pageSize];
        }
        try {
            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM login_logs WHERE user_id = :user_id');
            $countStmt->execute(['user_id' => $userId]);
            $total = (int) $countStmt->fetchColumn();

            $offset = ($page - 1) * $pageSize;
            $stmt = $pdo->prepare("SELECT id, user_id, ip, user_agent, status, created_at FROM login_logs WHERE user_id = :user_id ORDER BY created_at DESC LIMIT {$pageSize} OFFSET {$offset}");
            $stmt->execute(['user_id' => $userId]);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return ['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize];
        } catch (\PDOException $e) {
            error_log("[LoginLogRepository] listByUserId failed: " . $e->getMessage());
            return ['list' => [], 'total' => 0, 'page' => $page, 'page_size' => $pageSize];
        }
    }

    // 严格版本：DB 故障抛 RuntimeException，避免登录记录接口用"空列表"掩盖故障。
    public function listByUserIdStrict(int $userId, int $page = 1, int $pageSize = 20): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM login_logs WHERE user_id = :user_id');
            $countStmt->execute(['user_id' => $userId]);
            $total = (int) $countStmt->fetchColumn();

            $offset = ($page - 1) * $pageSize;
            $stmt = $pdo->prepare("SELECT id, user_id, ip, user_agent, status, created_at FROM login_logs WHERE user_id = :user_id ORDER BY created_at DESC LIMIT {$pageSize} OFFSET {$offset}");
            $stmt->execute(['user_id' => $userId]);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return ['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize];
        } catch (\PDOException $e) {
            throw new \RuntimeException('login log list failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function create(array $data): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $stmt = $pdo->prepare('INSERT INTO login_logs (user_id, ip, user_agent, status, created_at) VALUES (:user_id, :ip, :user_agent, :status, NOW())');
            return $stmt->execute([
                'user_id' => $data['user_id'] ?? 0,
                'ip' => $data['ip'] ?? '',
                'user_agent' => $data['user_agent'] ?? '',
                'status' => $data['status'] ?? 1,
            ]);
        } catch (\PDOException $e) {
            error_log("[LoginLogRepository] create failed: " . $e->getMessage());
            return false;
        }
    }
}
