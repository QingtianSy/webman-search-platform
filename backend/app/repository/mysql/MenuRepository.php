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
            $stmt = $pdo->query('SELECT id, parent_id, name, path, permission_code, sort, status, created_at, updated_at FROM menus WHERE status = 1 ORDER BY sort ASC, id ASC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[MenuRepository] all failed: " . $e->getMessage());
            return [];
        }
    }
}
