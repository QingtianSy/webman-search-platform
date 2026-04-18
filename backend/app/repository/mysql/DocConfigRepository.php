<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class DocConfigRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/doc_config.json';
    }

    public function get(): array
    {
        return config('integration.config_source', 'mock') === 'real'
            ? $this->getReal()
            : $this->getMock();
    }

    protected function getMock(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $row = json_decode((string) file_get_contents($this->file), true);
        return is_array($row) ? $row : [];
    }

    protected function getReal(): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare(
                "SELECT config_value FROM system_configs WHERE config_key = 'doc_config' AND status = 1 LIMIT 1"
            );
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row || empty($row['config_value'])) {
                return [];
            }
            $data = json_decode($row['config_value'], true);
            return is_array($data) ? $data : [];
        } catch (\PDOException $e) {
            error_log("[DocConfigRepository] getReal failed: " . $e->getMessage());
            return [];
        }
    }
}
