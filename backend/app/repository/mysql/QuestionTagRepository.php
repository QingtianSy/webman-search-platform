<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class QuestionTagRepository
{
    public function all(): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->query('SELECT id, name, sort, created_at, updated_at FROM question_tags ORDER BY sort ASC, id ASC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[QuestionTagRepository] all failed: " . $e->getMessage());
            return [];
        }
    }
}
