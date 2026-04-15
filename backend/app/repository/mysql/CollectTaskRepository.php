<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class CollectTaskRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/collect_tasks.json';
    }

    protected function allRows(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        return is_array($rows) ? $rows : [];
    }

    protected function saveAll(array $rows): void
    {
        file_put_contents($this->file, json_encode(array_values($rows), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    public function listByUserId(int $userId): array
    {
        return config('integration.collect_source', 'mock') === 'real'
            ? $this->listByUserIdReal($userId)
            : array_values(array_filter($this->allRows(), fn ($row) => (int) ($row['user_id'] ?? 0) === $userId));
    }

    public function updateStatus(string $taskNo, int $status, string $errorMessage = ''): array
    {
        return config('integration.collect_source', 'mock') === 'real'
            ? $this->updateStatusReal($taskNo, $status, $errorMessage)
            : $this->updateStatusMock($taskNo, $status, $errorMessage);
    }

    protected function updateStatusMock(string $taskNo, int $status, string $errorMessage = ''): array
    {
        $rows = $this->allRows();
        foreach ($rows as &$row) {
            if (($row['task_no'] ?? '') === $taskNo) {
                $row['status'] = $status;
                $row['error_message'] = $errorMessage;
                $this->saveAll($rows);
                return $row;
            }
        }
        return [];
    }

    protected function listByUserIdReal(int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        $stmt = $pdo->prepare('SELECT id, task_no, user_id, account_id, collect_type, course_count, question_count, success_count, fail_count, status, error_message, runner_script, next_script, created_at FROM collect_tasks WHERE user_id = :user_id ORDER BY id DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    protected function updateStatusReal(string $taskNo, int $status, string $errorMessage = ''): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        $stmt = $pdo->prepare('UPDATE collect_tasks SET status = :status, error_message = :error_message, updated_at = NOW() WHERE task_no = :task_no');
        $stmt->execute([
            'task_no' => $taskNo,
            'status' => $status,
            'error_message' => $errorMessage,
        ]);
        return [
            'task_no' => $taskNo,
            'status' => $status,
            'error_message' => $errorMessage,
        ];
    }
}
