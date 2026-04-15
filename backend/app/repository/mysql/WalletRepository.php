<?php

namespace app\repository\mysql;

class WalletRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/wallets.json';
    }

    public function findByUserId(int $userId): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        if (!is_array($rows)) {
            return [];
        }
        foreach ($rows as $row) {
            if ((int) ($row['user_id'] ?? 0) === $userId) {
                return $row;
            }
        }
        return [];
    }
}
