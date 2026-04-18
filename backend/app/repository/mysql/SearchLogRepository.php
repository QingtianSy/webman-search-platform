<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class SearchLogRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/logs/search_logs.jsonl';
    }

    public function create(array $data): bool
    {
        return config('integration.log_source', 'mock') === 'real'
            ? $this->createReal($data)
            : $this->createMock($data);
    }

    protected function createMock(array $data): bool
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $line = json_encode($data, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        return file_put_contents($this->file, $line, FILE_APPEND | LOCK_EX) !== false;
    }

    protected function createReal(array $data): bool
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
            error_log("[SearchLogRepository] createReal failed: " . $e->getMessage());
            return false;
        }
    }
}
