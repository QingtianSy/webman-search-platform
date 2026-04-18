<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class QuestionTagRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/question_tags.json';
    }

    public function all(): array
    {
        return config('integration.question_source', 'mock') === 'real'
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
            $stmt = $pdo->query('SELECT id, name, sort, created_at, updated_at FROM question_tags ORDER BY sort ASC, id ASC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[QuestionTagRepository] allReal failed: " . $e->getMessage());
            return [];
        }
    }
}
