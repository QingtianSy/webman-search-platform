<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class QuestionSourceRepository
{
    public function all(): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->query('SELECT id, name, code, url, status, created_at, updated_at FROM question_sources ORDER BY id ASC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[QuestionSourceRepository] all failed: " . $e->getMessage());
            return [];
        }
    }
}
