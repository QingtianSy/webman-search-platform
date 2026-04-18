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
            $stmt = $pdo->prepare('SELECT id, user_id, app_name, api_key, api_secret_hash, status, expire_at, created_at, updated_at FROM user_api_keys WHERE user_id = :user_id ORDER BY id DESC');
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[ApiKeyRepository] findByUserId failed: " . $e->getMessage());
            return [];
        }
    }

    public function delete(int $id): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $stmt = $pdo->prepare('DELETE FROM user_api_keys WHERE id = :id');
            return $stmt->execute(['id' => $id]);
        } catch (\PDOException $e) {
            error_log("[ApiKeyRepository] delete failed: " . $e->getMessage());
            return false;
        }
    }

    public function toggle(int $id, int $status): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $stmt = $pdo->prepare('UPDATE user_api_keys SET status = :status, updated_at = NOW() WHERE id = :id');
            return $stmt->execute(['status' => $status, 'id' => $id]);
        } catch (\PDOException $e) {
            error_log("[ApiKeyRepository] toggle failed: " . $e->getMessage());
            return false;
        }
    }
}
