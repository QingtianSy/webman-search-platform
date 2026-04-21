<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class MenuRepository
{
    public function all(): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->query('SELECT id, parent_id, name, path, permission_code, sort, status, created_at, updated_at FROM menus WHERE status = 1 ORDER BY sort ASC, id ASC LIMIT 10000');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[MenuRepository] all failed: " . $e->getMessage());
            return [];
        }
    }

    public function findByPermissionCodes(array $codes): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo || empty($codes)) {
            return [];
        }
        try {
            $placeholders = implode(',', array_fill(0, count($codes), '?'));
            $stmt = $pdo->prepare("SELECT id, parent_id, name, path, permission_code, sort, status, created_at, updated_at FROM menus WHERE status = 1 AND permission_code IN ({$placeholders}) ORDER BY sort ASC, id ASC");
            $stmt->execute(array_values($codes));
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[MenuRepository] findByPermissionCodes failed: " . $e->getMessage());
            return [];
        }
    }

    // 严格版本：DB 故障抛 RuntimeException，避免菜单接口把"DB 挂了"伪装成"该用户无可见菜单"（200 + []）。
    public function findByPermissionCodesStrict(array $codes): array
    {
        if (empty($codes)) {
            return [];
        }
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $placeholders = implode(',', array_fill(0, count($codes), '?'));
            $stmt = $pdo->prepare("SELECT id, parent_id, name, path, permission_code, sort, status, created_at, updated_at FROM menus WHERE status = 1 AND permission_code IN ({$placeholders}) ORDER BY sort ASC, id ASC");
            $stmt->execute(array_values($codes));
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            throw new \RuntimeException('menu list failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
