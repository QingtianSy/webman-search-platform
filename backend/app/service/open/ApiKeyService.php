<?php

namespace app\service\open;

use app\repository\mysql\ApiKeyRepository;

class ApiKeyService
{
    public function verify(?string $apiKey, ?string $apiSecret): bool
    {
        if (empty($apiKey) || empty($apiSecret)) {
            return false;
        }
        $record = (new ApiKeyRepository())->findByApiKey($apiKey);
        if (!$record) {
            return false;
        }
        if ((int) ($record['status'] ?? 0) !== 1) {
            return false;
        }
        return ($record['api_secret'] ?? '') === $apiSecret;
    }

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

    public function mockCreate(int $userId, string $appName): array
    {
        return [
            'id' => 999,
            'user_id' => $userId,
            'app_name' => $appName !== '' ? $appName : '新应用',
            'api_key' => 'ak_mock_new',
            'api_secret' => 'sk_mock_new',
            'status' => 1,
            'expire_at' => null,
        ];
    }
}
