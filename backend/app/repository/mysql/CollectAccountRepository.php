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
            $stmt = $pdo->prepare('SELECT id, user_id, platform, account, cookie_text, token_text, status, remark, created_at, updated_at FROM collect_accounts WHERE user_id = :user_id ORDER BY id DESC LIMIT 10000');
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[CollectAccountRepository] listByUserId failed: " . $e->getMessage());
            return [];
        }
    }

    public function countByUserId(int $userId): int
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return 0;
        }
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM collect_accounts WHERE user_id = :user_id');
            $stmt->execute(['user_id' => $userId]);
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log("[CollectAccountRepository] countByUserId failed: " . $e->getMessage());
            return 0;
        }
    }

    public function findPageByUserId(int $userId, int $page, int $pageSize): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $offset = ($page - 1) * $pageSize;
            $stmt = $pdo->prepare('SELECT id, user_id, platform, account, cookie_text, token_text, status, remark, created_at, updated_at FROM collect_accounts WHERE user_id = :user_id ORDER BY id DESC LIMIT :limit OFFSET :offset');
            $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue('limit', $pageSize, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[CollectAccountRepository] findPageByUserId failed: " . $e->getMessage());
            return [];
        }
    }
}
