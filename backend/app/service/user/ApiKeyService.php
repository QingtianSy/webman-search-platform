<?php

namespace app\service\user;

use app\exception\BusinessException;
use app\repository\mysql\ApiKeyRepository;
use PDO;
use support\adapter\MySqlClient;

class ApiKeyService
{
    public function listByUserId(int $userId): array
    {
        return (new ApiKeyRepository())->findByUserId($userId);
    }

    public function detailById(int $userId, int $id): array
    {
        foreach ($this->listByUserId($userId) as $row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                return $row;
            }
        }
        return [];
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
            error_log("[ApiKeyService] create failed: " . $e->getMessage());
            return [];
        }
    }

    public function toggle(int $userId, int $id, int $status): bool
    {
        $ok = (new ApiKeyRepository())->toggle($userId, $id, $status);
        if (!$ok) {
            throw new BusinessException('API Key 不存在', 40001);
        }
        return true;
    }

    public function delete(int $userId, int $id): bool
    {
        $ok = (new ApiKeyRepository())->delete($userId, $id);
        if (!$ok) {
            throw new BusinessException('API Key 不存在', 40001);
        }
        return true;
    }
}
