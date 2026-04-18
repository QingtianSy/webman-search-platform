<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class CollectAccountRepository
{
    public function listByUserId(int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('SELECT id, user_id, platform, account, cookie_text, token_text, status, remark, created_at, updated_at FROM collect_accounts WHERE user_id = :user_id ORDER BY id DESC');
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[CollectAccountRepository] listByUserId failed: " . $e->getMessage());
            return [];
        }
    }
}
