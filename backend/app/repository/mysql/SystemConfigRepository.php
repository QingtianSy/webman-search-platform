<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class SystemConfigRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/system_configs.json';
    }

    protected function allRows(): array
    {
        if (!is_file($this->file)) {
            error_log("[SystemConfigRepository] Mock file not found: {$this->file}");
            return [];
        }
        
        $content = @file_get_contents($this->file);
        if ($content === false) {
            error_log("[SystemConfigRepository] Failed to read mock file: {$this->file}");
            return [];
        }
        
        $rows = json_decode($content, true);
        if (!is_array($rows)) {
            error_log("[SystemConfigRepository] Invalid JSON in mock file: {$this->file}");
            return [];
        }
        
        return $rows;
    }

    public function all(): array
    {
        return config('integration.config_source', 'mock') === 'real'
            ? $this->allReal()
            : $this->allRows();
    }

    public function updateByKey(string $key, string $value): array
    {
        return config('integration.config_source', 'mock') === 'real'
            ? $this->updateByKeyReal($key, $value)
            : $this->updateByKeyMock($key, $value);
    }

    protected function updateByKeyMock(string $key, string $value): array
    {
        $rows = $this->allRows();
        foreach ($rows as &$row) {
            if (($row['config_key'] ?? '') === $key) {
                $row['config_value'] = $value;
                file_put_contents($this->file, json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                return $row;
            }
        }
        return [];
    }

    protected function allReal(): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            error_log("[SystemConfigRepository] Database connection failed");
            return [];
        }
        
        try {
            $stmt = $pdo->query('SELECT id, config_group, config_key, config_value, config_type, status, created_at, updated_at FROM system_configs ORDER BY id DESC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[SystemConfigRepository] Query failed: " . $e->getMessage());
            return [];
        }
    }

    protected function updateByKeyReal(string $key, string $value): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        $stmt = $pdo->prepare('UPDATE system_configs SET config_value = :config_value, updated_at = NOW() WHERE config_key = :config_key');
        $stmt->execute([
            'config_key' => $key,
            'config_value' => $value,
        ]);
        $stmt = $pdo->prepare('SELECT id, config_group, config_key, config_value, config_type, status, created_at, updated_at FROM system_configs WHERE config_key = :config_key LIMIT 1');
        $stmt->execute(['config_key' => $key]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}
