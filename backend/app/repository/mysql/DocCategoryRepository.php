<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class DocCategoryRepository
{
    public function all(): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->query('SELECT id, name, slug, sort, status, created_at, updated_at FROM docs_categories ORDER BY sort ASC, id ASC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[DocCategoryRepository] all failed: " . $e->getMessage());
            return [];
        }
    }
}
