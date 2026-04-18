<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class LoginLogRepository
{
    public function listByUserId(int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('SELECT id, user_id, ip, user_agent, status, created_at FROM login_logs WHERE user_id = :user_id ORDER BY created_at DESC');
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[LoginLogRepository] listByUserId failed: " . $e->getMessage());
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
