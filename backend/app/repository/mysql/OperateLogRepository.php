<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class OperateLogRepository
{
    public function listByUserId(int $userId, int $page = 1, int $pageSize = 20): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return ['list' => [], 'total' => 0, 'page' => $page, 'page_size' => $pageSize];
        }
        try {
            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM operate_logs WHERE user_id = :user_id');
            $countStmt->execute(['user_id' => $userId]);
            $total = (int) $countStmt->fetchColumn();

            $offset = ($page - 1) * $pageSize;
            $stmt = $pdo->prepare("SELECT id, user_id, module, action, content, ip, created_at FROM operate_logs WHERE user_id = :user_id ORDER BY created_at DESC LIMIT {$pageSize} OFFSET {$offset}");
            $stmt->execute(['user_id' => $userId]);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return ['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize];
        } catch (\PDOException $e) {
            error_log("[OperateLogRepository] listByUserId failed: " . $e->getMessage());
            return ['list' => [], 'total' => 0, 'page' => $page, 'page_size' => $pageSize];
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
