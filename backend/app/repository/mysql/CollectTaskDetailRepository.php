<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class CollectTaskDetailRepository
{
    public function findByTaskNo(string $taskNo, ?int $userId = null): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $sql = 'SELECT id, task_no, user_id, account_id, account_phone, collect_type, course_ids, '
                . 'course_count, question_count, success_count, fail_count, status, error_message, courses_snapshot,'
                . 'runner_script, next_script, created_at, updated_at '
                . 'FROM collect_tasks WHERE task_no = :task_no';
            $params = ['task_no' => $taskNo];
            if ($userId !== null) {
                $sql .= ' AND user_id = :user_id';
                $params['user_id'] = $userId;
            }
            $sql .= ' LIMIT 1';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: [];
        } catch (\PDOException $e) {
            error_log("[CollectTaskDetailRepository] findByTaskNo failed: " . $e->getMessage());
            return [];
        }
    }

    public function findByTaskNoStrict(string $taskNo, ?int $userId = null): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $sql = 'SELECT id, task_no, user_id, account_id, account_phone, collect_type, course_ids, '
                . 'course_count, question_count, success_count, fail_count, status, error_message, courses_snapshot,'
                . 'runner_script, next_script, created_at, updated_at '
                . 'FROM collect_tasks WHERE task_no = :task_no';
            $params = ['task_no' => $taskNo];
            if ($userId !== null) {
                $sql .= ' AND user_id = :user_id';
                $params['user_id'] = $userId;
            }
            $sql .= ' LIMIT 1';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: [];
        } catch (\PDOException $e) {
            error_log("[CollectTaskDetailRepository] findByTaskNoStrict failed: " . $e->getMessage());
            throw new \RuntimeException('collect task query failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
