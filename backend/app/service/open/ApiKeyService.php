<?php

namespace app\service\open;

use app\repository\mysql\ApiKeyRepository;
use app\repository\mysql\UserRepository;
use PDO;
use support\adapter\MySqlClient;

class ApiKeyService
{
    public function verify(?string $apiKey, ?string $apiSecret): ?array
    {
        if (empty($apiKey) || empty($apiSecret)) {
            return null;
        }
        $record = (new ApiKeyRepository())->findByApiKey($apiKey);
        if (!$record) {
            return null;
        }
        if ((int) ($record['status'] ?? 0) !== 1) {
            return null;
        }
        if (!empty($record['expire_at']) && strtotime($record['expire_at']) < time()) {
            return null;
        }
        $userId = (int) ($record['user_id'] ?? 0);
        if ($userId > 0) {
            $user = (new UserRepository())->findById($userId);
            if (!$user || (int) ($user['status'] ?? 0) !== 1) {
                return null;
            }
        }

        if (!empty($record['api_secret_hash'])) {
            return password_verify($apiSecret, $record['api_secret_hash']) ? $record : null;
        }
        return (($record['api_secret'] ?? '') === $apiSecret) ? $record : null;
    }

    public function listByUserId(int $userId): array
    {
        return (new ApiKeyRepository())->findByUserId($userId);
    }

    public function detailById(int $userId, int $id): array
    {
        return (new ApiKeyRepository())->findByIdAndUserId($id, $userId);
    }

    public function create(int $userId, string $appName): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        $apiKey = 'ak_' . bin2hex(random_bytes(16));
        $apiSecret = 'sk_' . bin2hex(random_bytes(24));
        $secretHash = password_hash($apiSecret, PASSWORD_BCRYPT);
        try {
            $stmt = $pdo->prepare('INSERT INTO user_api_keys (user_id, app_name, api_key, api_secret_hash, status, created_at, updated_at) VALUES (:user_id, :app_name, :api_key, :api_secret_hash, 1, NOW(), NOW())');
            $stmt->execute([
                'user_id' => $userId,
                'app_name' => $appName !== '' ? $appName : '新应用',
                'api_key' => $apiKey,
                'api_secret_hash' => $secretHash,
            ]);
            return [
                'id' => (int) $pdo->lastInsertId(),
                'user_id' => $userId,
                'app_name' => $appName,
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        } catch (\PDOException $e) {
            error_log("[OpenApiKeyService] create failed: " . $e->getMessage());
            return [];
        }
    }

    public function delete(int $userId, int $id): bool
    {
        return (new ApiKeyRepository())->delete($userId, $id);
    }
}
