<?php

namespace app\repository\mongo;

use support\adapter\MongoClient;

class SearchLogDetailRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/logs/search_log_details.jsonl';
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
        $db = MongoClient::connection();
        if (!$db) {
            return $this->createMock($data);
        }
        try {
            $data['created_at'] = date('Y-m-d H:i:s');
            $db->selectCollection('search_log_details')->insertOne($data);
            return true;
        } catch (\Throwable $e) {
            error_log("[SearchLogDetailRepository] createReal failed: " . $e->getMessage());
            return $this->createMock($data);
        }
    }
}
