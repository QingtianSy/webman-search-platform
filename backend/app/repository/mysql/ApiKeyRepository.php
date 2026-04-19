<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class ApiKeyRepository
{
    public function findByApiKey(string $apiKey): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('SELECT id, user_id, app_name, api_key, api_secret_hash, status, expire_at, created_at, updated_at FROM user_api_keys WHERE api_key = :api_key LIMIT 1');
            $stmt->execute(['api_key' => $apiKey]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[ApiKeyRepository] findByApiKey failed: " . $e->getMessage());
            return [];
        }
    }

    public function findByUserId(int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('SELECT id, user_id, app_name, api_key, api_secret_hash, status, expire_at, created_at, updated_at FROM user_api_keys WHERE user_id = :user_id ORDER BY id DESC LIMIT 10000');
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[ApiKeyRepository] findByUserId failed: " . $e->getMessage());
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
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM user_api_keys WHERE user_id = :user_id');
            $stmt->execute(['user_id' => $userId]);
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log("[ApiKeyRepository] countByUserId failed: " . $e->getMessage());
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
            $stmt = $pdo->prepare('SELECT id, user_id, app_name, api_key, api_secret_hash, status, expire_at, created_at, updated_at FROM user_api_keys WHERE user_id = :user_id ORDER BY id DESC LIMIT :limit OFFSET :offset');
            $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue('limit', $pageSize, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[ApiKeyRepository] findPageByUserId failed: " . $e->getMessage());
            return [];
        }
    }

    public function delete(int $userId, int $id): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $stmt = $pdo->prepare('DELETE FROM user_api_keys WHERE id = :id AND user_id = :user_id');
            $stmt->execute(['id' => $id, 'user_id' => $userId]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("[ApiKeyRepository] delete failed: " . $e->getMessage());
            return false;
        }
    }

    public function toggle(int $userId, int $id, int $status): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $check = $pdo->prepare('SELECT id FROM user_api_keys WHERE id = :id AND user_id = :user_id');
            $check->execute(['id' => $id, 'user_id' => $userId]);
            if (!$check->fetch()) {
                return false;
            }
            $stmt = $pdo->prepare('UPDATE user_api_keys SET status = :status, updated_at = NOW() WHERE id = :id AND user_id = :user_id');
            $stmt->execute(['status' => $status, 'id' => $id, 'user_id' => $userId]);
            return true;
        } catch (\PDOException $e) {
            error_log("[ApiKeyRepository] toggle failed: " . $e->getMessage());
            return false;
        }
    }
}
