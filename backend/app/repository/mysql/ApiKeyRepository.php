<?php

namespace app\repository\mysql;

class ApiKeyRepository
{
    public function findByApiKey(string $apiKey): array
    {
        return $apiKey !== '' ? [
            'id' => 1,
            'user_id' => 1,
            'api_key' => $apiKey,
            'status' => 1,
        ] : [];
    }
}
