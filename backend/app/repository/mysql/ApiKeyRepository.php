<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class ApiKeyRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/api_keys.json';
    }

    protected function all(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        return is_array($rows) ? $rows : [];
    }

    protected function saveAll(array $rows): void
    {
        file_put_contents($this->file, json_encode(array_values($rows), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    }

    public function findByApiKey(string $apiKey): array
    {
        return config('integration.user_center_source', 'mock') === 'real'
            ? $this->findByApiKeyReal($apiKey)
            : $this->findByApiKeyMock($apiKey);
    }

    public function findByUserId(int $userId): array
    {
        return config('integration.user_center_source', 'mock') === 'real'
            ? $this->findByUserIdReal($userId)
            : array_values(array_filter($this->all(), fn ($row) => (int) ($row['user_id'] ?? 0) === $userId));
    }

    public function delete(int $id): bool
    {
        if (config('integration.user_center_source', 'mock') === 'real') {
            return $this->deleteReal($id);
        }
        $rows = array_values(array_filter($this->all(), fn ($row) => (int) ($row['id'] ?? 0) !== $id));
        $this->saveAll($rows);
        return true;
    }

    public function toggle(int $id, int $status): bool
    {
        if (config('integration.user_center_source', 'mock') === 'real') {
            return $this->toggleReal($id, $status);
        }
        $rows = $this->all();
        foreach ($rows as &$row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                $row['status'] = $status;
                $this->saveAll($rows);
                return true;
            }
        }
        return false;
    }

    protected function findByApiKeyMock(string $apiKey): array
    {
        foreach ($this->all() as $row) {
            if (($row['api_key'] ?? '') === $apiKey) {
                return $row;
            }
        }
        return [];
    }

    protected function findByApiKeyReal(string $apiKey): array
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
                error_log("[ApiKeyRepository] findByApiKeyReal failed: " . $e->getMessage());
                return [];
            }
        }

    protected function findByUserIdReal(int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        $stmt = $pdo->prepare('SELECT id, user_id, app_name, api_key, api_secret_hash, status, expire_at, created_at, updated_at FROM user_api_keys WHERE user_id = :user_id ORDER BY id DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    protected function deleteReal(int $id): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }
        $stmt = $pdo->prepare('DELETE FROM user_api_keys WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    protected function toggleReal(int $id, int $status): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $stmt = $pdo->prepare('UPDATE user_api_keys SET status = :status, updated_at = NOW() WHERE id = :id');
            return $stmt->execute(['status' => $status, 'id' => $id]);
        } catch (\PDOException $e) {
            error_log("[ApiKeyRepository] toggleReal failed: " . $e->getMessage());
            return false;
        }
    }
}
