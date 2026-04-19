<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class CollectTaskRepository
{
    public function all(): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->query('SELECT id, task_no, user_id, account_id, collect_type, course_count, question_count, success_count, fail_count, status, error_message, runner_script, next_script, created_at FROM collect_tasks ORDER BY id DESC LIMIT 10000');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[CollectTaskRepository] all failed: " . $e->getMessage());
            return [];
        }
    }

    public function countAll(?int $userId = null): int
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return 0;
        }
        try {
            if ($userId !== null && $userId > 0) {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM collect_tasks WHERE user_id = :user_id');
                $stmt->execute(['user_id' => $userId]);
            } else {
                $stmt = $pdo->query('SELECT COUNT(*) FROM collect_tasks');
            }
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log("[CollectTaskRepository] countAll failed: " . $e->getMessage());
            return 0;
        }
    }

    public function findPage(int $page, int $pageSize, ?int $userId = null): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $offset = ($page - 1) * $pageSize;
            if ($userId !== null && $userId > 0) {
                $stmt = $pdo->prepare('SELECT id, task_no, user_id, account_id, collect_type, course_count, question_count, success_count, fail_count, status, error_message, runner_script, next_script, created_at FROM collect_tasks WHERE user_id = :user_id ORDER BY id DESC LIMIT :limit OFFSET :offset');
                $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
            } else {
                $stmt = $pdo->prepare('SELECT id, task_no, user_id, account_id, collect_type, course_count, question_count, success_count, fail_count, status, error_message, runner_script, next_script, created_at FROM collect_tasks ORDER BY id DESC LIMIT :limit OFFSET :offset');
            }
            $stmt->bindValue('limit', $pageSize, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[CollectTaskRepository] findPage failed: " . $e->getMessage());
            return [];
        }
    }

    public function listByUserId(int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('SELECT id, task_no, user_id, account_id, collect_type, course_count, question_count, success_count, fail_count, status, error_message, runner_script, next_script, created_at FROM collect_tasks WHERE user_id = :user_id ORDER BY id DESC LIMIT 10000');
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[CollectTaskRepository] listByUserId failed: " . $e->getMessage());
            return [];
        }
    }

    public function updateStatus(string $taskNo, int $status, string $errorMessage = ''): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
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
        } catch (\PDOException $e) {
            error_log("[CollectTaskRepository] updateStatus failed: " . $e->getMessage());
            return [];
        }
    }

    public function clearRunnerScript(string $taskNo): void
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return;
        }
        try {
            $stmt = $pdo->prepare('UPDATE collect_tasks SET runner_script = NULL, next_script = NULL WHERE task_no = :task_no');
            $stmt->execute(['task_no' => $taskNo]);
        } catch (\PDOException $e) {
            error_log("[CollectTaskRepository] clearRunnerScript failed: " . $e->getMessage());
        }
    }

    public function create(array $data): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        $stmt = $pdo->prepare(
            'INSERT INTO collect_tasks (task_no, user_id, account_id, account_phone, account_password, school_name, province, city, proxy_url, collect_type, course_ids, course_count, status, created_at, updated_at) '
            . 'VALUES (:task_no, :user_id, :account_id, :account_phone, :account_password, :school_name, :province, :city, :proxy_url, :collect_type, :course_ids, :course_count, 0, NOW(), NOW())'
        );
        $stmt->execute([
            'task_no' => $data['task_no'],
            'user_id' => $data['user_id'],
            'account_id' => $data['account_id'] ?? 0,
            'account_phone' => $data['account_phone'] ?? '',
            'account_password' => $data['account_password'] ?? '',
            'school_name' => $data['school_name'] ?? null,
            'province' => $data['province'] ?? null,
            'city' => $data['city'] ?? null,
            'proxy_url' => $data['proxy_url'] ?? null,
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

    public function findByStatus(int $status): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        $stmt = $pdo->prepare('SELECT * FROM collect_tasks WHERE status = :status ORDER BY id ASC');
        $stmt->execute(['status' => $status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
