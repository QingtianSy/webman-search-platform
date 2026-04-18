<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class SearchLogRepository
{
    public function create(array $data): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $stmt = $pdo->prepare('INSERT INTO search_logs (log_no, user_id, api_key_id, keyword, question_type, status, hit_count, source_type, consume_quota, cost_ms, created_at) VALUES (:log_no, :user_id, :api_key_id, :keyword, :question_type, :status, :hit_count, :source_type, :consume_quota, :cost_ms, NOW())');
            return $stmt->execute([
                'log_no' => $data['log_no'] ?? uniqid('SL'),
                'user_id' => $data['user_id'] ?? null,
                'api_key_id' => $data['api_key_id'] ?? null,
                'keyword' => $data['keyword'] ?? '',
                'question_type' => $data['question_type'] ?? null,
                'status' => $data['status'] ?? 1,
                'hit_count' => $data['hit_count'] ?? 0,
                'source_type' => $data['source_type'] ?? 'local',
                'consume_quota' => $data['consume_quota'] ?? 0,
                'cost_ms' => $data['cost_ms'] ?? 0,
            ]);
        } catch (\PDOException $e) {
            error_log("[SearchLogRepository] create failed: " . $e->getMessage());
            return false;
        }
    }
}
