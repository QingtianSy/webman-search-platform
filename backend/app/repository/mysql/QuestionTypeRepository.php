<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class QuestionTypeRepository
{
    public function all(): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->query('SELECT id, code, name, sort, status, created_at, updated_at FROM question_types ORDER BY sort ASC, id ASC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[QuestionTypeRepository] all failed: " . $e->getMessage());
            return [];
        }
    }
}
