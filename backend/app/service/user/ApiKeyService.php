<?php

namespace app\service\user;

use app\repository\mysql\ApiKeyRepository;

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

    public function toggle(int $id, int $status): bool
    {
        return (new ApiKeyRepository())->toggle($id, $status);
    }

    public function delete(int $id): bool
    {
        return (new ApiKeyRepository())->delete($id);
    }
}
