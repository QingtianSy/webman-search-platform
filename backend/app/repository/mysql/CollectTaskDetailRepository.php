<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class CollectTaskDetailRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/collect_task_details.json';
    }

    public function findByTaskNo(string $taskNo): array
    {
        return config('integration.collect_source', 'mock') === 'real'
            ? $this->findByTaskNoReal($taskNo)
            : $this->findByTaskNoMock($taskNo);
    }

    protected function findByTaskNoMock(string $taskNo): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        $rows = is_array($rows) ? $rows : [];
        foreach ($rows as $row) {
            if (($row['task_no'] ?? '') === $taskNo) {
                return $row;
            }
        }
        return [];
    }

    protected function findByTaskNoReal(string $taskNo): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare(
                'SELECT id, task_no, user_id, account_id, account_phone, collect_type, course_ids, '
                . 'course_count, question_count, success_count, fail_count, status, error_message, '
                . 'runner_script, next_script, created_at, updated_at '
                . 'FROM collect_tasks WHERE task_no = :task_no LIMIT 1'
            );
            $stmt->execute(['task_no' => $taskNo]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: [];
        } catch (\PDOException $e) {
            error_log("[CollectTaskDetailRepository] findByTaskNoReal failed: " . $e->getMessage());
            return [];
        }
    }
}
