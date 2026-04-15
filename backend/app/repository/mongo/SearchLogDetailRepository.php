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
        return file_put_contents($this->file, $line, FILE_APPEND) !== false;
    }

    protected function createReal(array $data): bool
    {
        if (!MongoClient::isConfigured()) {
            return false;
        }

        /**
         * 未来真实写入示意：
         * db.search_log_details.insertOne({...})
         */
        return true;
    }
}
