<?php

namespace app\repository\mysql;

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

    public function findByApiKey(string $apiKey): array
    {
        foreach ($this->all() as $row) {
            if (($row['api_key'] ?? '') === $apiKey) {
                return $row;
            }
        }
        return [];
    }

    public function findByUserId(int $userId): array
    {
        return array_values(array_filter($this->all(), fn ($row) => (int) ($row['user_id'] ?? 0) === $userId));
    }
}
