<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class WalletRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/wallets.json';
    }

    public function findByUserId(int $userId): array
    {
        return config('integration.user_center_source', 'mock') === 'real'
            ? $this->findByUserIdReal($userId)
            : $this->findByUserIdMock($userId);
    }

    protected function findByUserIdMock(int $userId): array
    {
        if (!is_file($this->file)) {
            error_log("[WalletRepository] Mock file not found: {$this->file}");
            return [];
        }
        
        $content = @file_get_contents($this->file);
        if ($content === false) {
            error_log("[WalletRepository] Failed to read mock file: {$this->file}");
            return [];
        }
        
        $rows = json_decode($content, true);
        if (!is_array($rows)) {
            error_log("[WalletRepository] Invalid JSON in mock file: {$this->file}");
            return [];
        }
        
        foreach ($rows as $row) {
            if ((int) ($row['user_id'] ?? 0) === $userId) {
                return $row;
            }
        }
        return [];
    }

    protected function findByUserIdReal(int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            error_log("[WalletRepository] Database connection failed");
            return [];
        }
        
        try {
            $stmt = $pdo->prepare('SELECT id, user_id, balance, frozen_balance, total_recharge, total_consume, created_at, updated_at FROM wallets WHERE user_id = :user_id LIMIT 1');
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[WalletRepository] Query failed: " . $e->getMessage());
            return [];
        }
    }
}
