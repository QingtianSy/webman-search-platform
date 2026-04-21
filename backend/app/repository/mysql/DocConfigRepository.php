<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class DocConfigRepository
{
    public function get(): array
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
            error_log("[DocConfigRepository] get failed: " . $e->getMessage());
            return [];
        }
    }

    // 严格版本：DB 故障抛 RuntimeException，避免文档配置接口用空对象掩盖故障。
    public function getStrict(): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
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
            throw new \RuntimeException('doc config get failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
