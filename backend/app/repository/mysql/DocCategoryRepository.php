<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class DocCategoryRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/docs_categories.json';
    }

    public function all(): array
    {
        return config('integration.docs_source', 'mock') === 'real'
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
            $stmt = $pdo->query('SELECT id, name, slug, sort, status, created_at, updated_at FROM docs_categories ORDER BY sort ASC, id ASC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (\PDOException $e) {
                error_log("[DocCategoryRepository] allReal failed: " . $e->getMessage());
                return [];
            }
        }
}
