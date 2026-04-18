<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class QuestionSourceRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/question_sources.json';
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
            $stmt = $pdo->query('SELECT id, name, code, url, status, created_at, updated_at FROM question_sources ORDER BY id ASC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[QuestionSourceRepository] allReal failed: " . $e->getMessage());
            return [];
        }
    }
}
