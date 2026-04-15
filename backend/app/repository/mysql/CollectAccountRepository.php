<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class CollectAccountRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/collect_accounts.json';
    }

    public function listByUserId(int $userId): array
    {
        return config('integration.collect_source', 'mock') === 'real'
            ? $this->listByUserIdReal($userId)
            : $this->listByUserIdMock($userId);
    }

    protected function listByUserIdMock(int $userId): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        $rows = is_array($rows) ? $rows : [];
        return array_values(array_filter($rows, fn ($row) => (int) ($row['user_id'] ?? 0) === $userId));
    }

    protected function listByUserIdReal(int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        $stmt = $pdo->prepare('SELECT id, user_id, platform, account, cookie_text, token_text, status, remark, created_at, updated_at FROM collect_accounts WHERE user_id = :user_id ORDER BY id DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
