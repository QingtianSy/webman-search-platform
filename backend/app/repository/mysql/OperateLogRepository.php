<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class OperateLogRepository
{
    public function listByUserId(int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('SELECT id, user_id, module, action, content, ip, created_at FROM operate_logs WHERE user_id = :user_id ORDER BY created_at DESC');
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[OperateLogRepository] listByUserId failed: " . $e->getMessage());
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
            $stmt = $pdo->prepare('INSERT INTO operate_logs (user_id, module, action, content, ip, created_at) VALUES (:user_id, :module, :action, :content, :ip, NOW())');
            return $stmt->execute([
                'user_id' => $data['user_id'] ?? 0,
                'module' => $data['module'] ?? '',
                'action' => $data['action'] ?? '',
                'content' => $data['content'] ?? '',
                'ip' => $data['ip'] ?? '',
            ]);
        } catch (\PDOException $e) {
            error_log("[OperateLogRepository] create failed: " . $e->getMessage());
            return false;
        }
    }
}
