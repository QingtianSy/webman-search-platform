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
}
