<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

/**
 * MenuRepository
 */
class MenuRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/menus.json';
    }

    public function all(): array
    {
        return config('integration.auth_rbac_source', 'mock') === 'real'
            ? $this->allReal()
            : $this->allMock();
    }

    protected function allMock(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        return is_array($rows) ? $rows : [];
    }

    protected function allReal(): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
            try {
            $stmt = $pdo->query('SELECT id, parent_id, name, path, permission_code, sort, status, created_at, updated_at FROM menus WHERE status = 1 ORDER BY sort ASC, id ASC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (\PDOException $e) {
                error_log("[MenuRepository] allReal failed: " . $e->getMessage());
                return [];
            }
        }
}
