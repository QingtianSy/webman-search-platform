<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class CollectTaskDetailRepository
{
    public function findByTaskNo(string $taskNo): array
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
            error_log("[CollectTaskDetailRepository] findByTaskNo failed: " . $e->getMessage());
            return [];
        }
    }
}
