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
            $stmt = $pdo->query('SELECT id, name, slug, sort, status, created_at, updated_at FROM docs_categories WHERE status = 1 ORDER BY sort ASC, id ASC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[DocCategoryRepository] all failed: " . $e->getMessage());
            return [];
        }
    }

    // 严格版本：DB 故障抛 RuntimeException，避免文档分类接口用"空列表"掩盖故障。
    public function allStrict(): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $stmt = $pdo->query('SELECT id, name, slug, sort, status, created_at, updated_at FROM docs_categories WHERE status = 1 ORDER BY sort ASC, id ASC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            throw new \RuntimeException('doc category list failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
