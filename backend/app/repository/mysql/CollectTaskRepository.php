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
        file_put_contents($this->file, json_encode(array_values($rows), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    }

    public function all(): array
    {
        return config('integration.collect_source', 'mock') === 'real'
            ? $this->allReal()
            : $this->allRows();
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

    protected function allReal(): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->query('SELECT id, task_no, user_id, account_id, collect_type, course_count, question_count, success_count, fail_count, status, error_message, runner_script, next_script, created_at FROM collect_tasks ORDER BY id DESC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[CollectTaskRepository] allReal failed: " . $e->getMessage());
            return [];
        }
    }

    protected function listByUserIdReal(int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
            try {
            $stmt = $pdo->prepare('SELECT id, task_no, user_id, account_id, collect_type, course_count, question_count, success_count, fail_count, status, error_message, runner_script, next_script, created_at FROM collect_tasks WHERE user_id = :user_id ORDER BY id DESC');
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (\PDOException $e) {
                error_log("[CollectTaskRepository] listByUserIdReal failed: " . $e->getMessage());
                return [];
            }
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

    public function create(array $data): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        $stmt = $pdo->prepare(
            'INSERT INTO collect_tasks (task_no, user_id, account_id, account_phone, account_password, collect_type, course_ids, course_count, status, created_at, updated_at) '
            . 'VALUES (:task_no, :user_id, :account_id, :account_phone, :account_password, :collect_type, :course_ids, :course_count, 0, NOW(), NOW())'
        );
        $stmt->execute([
            'task_no' => $data['task_no'],
            'user_id' => $data['user_id'],
            'account_id' => $data['account_id'] ?? 0,
            'account_phone' => $data['account_phone'] ?? '',
            'account_password' => $data['account_password'] ?? '',
            'collect_type' => $data['collect_type'] ?? 'courses',
            'course_ids' => $data['course_ids'] ?? '',
            'course_count' => $data['course_count'] ?? 0,
        ]);
        return $data;
    }

    public function claimPending(): ?array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return null;
        }
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT * FROM collect_tasks WHERE status = 0 ORDER BY id ASC LIMIT 1 FOR UPDATE');
            $stmt->execute();
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$task) {
                $pdo->commit();
                return null;
            }
            $update = $pdo->prepare('UPDATE collect_tasks SET status = 1, updated_at = NOW() WHERE id = :id');
            $update->execute(['id' => $task['id']]);
            $pdo->commit();
            $task['status'] = 1;
            return $task;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log("[CollectTaskRepository] claimPending failed: " . $e->getMessage());
            return null;
        }
    }

    public function updateRunnerPid(string $taskNo, int $pid): void
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return;
        }
        $stmt = $pdo->prepare('UPDATE collect_tasks SET runner_script = :pid, updated_at = NOW() WHERE task_no = :task_no');
        $stmt->execute(['pid' => (string) $pid, 'task_no' => $taskNo]);
    }

    public function updateProgress(string $taskNo, int $questionCount, int $successCount, int $failCount): void
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return;
        }
        $stmt = $pdo->prepare(
            'UPDATE collect_tasks SET question_count = :qc, success_count = :sc, fail_count = :fc, updated_at = NOW() WHERE task_no = :task_no'
        );
        $stmt->execute([
            'qc' => $questionCount,
            'sc' => $successCount,
            'fc' => $failCount,
            'task_no' => $taskNo,
        ]);
    }

    public function findByTaskNo(string $taskNo): ?array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return null;
        }
        $stmt = $pdo->prepare('SELECT * FROM collect_tasks WHERE task_no = :task_no LIMIT 1');
        $stmt->execute(['task_no' => $taskNo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
