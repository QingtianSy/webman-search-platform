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
        return file_put_contents($this->file, $line, FILE_APPEND) !== false;
    }

    protected function createReal(array $data): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }

        /**
         * 未来真实写入示意：
         * INSERT INTO search_logs (log_no, user_id, api_key_id, keyword, question_type, status, hit_count, source_type, consume_quota, cost_ms, created_at)
         * VALUES (...)
         */
        return true;
    }
}
