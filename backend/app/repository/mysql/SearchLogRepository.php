<?php

namespace app\repository\mysql;

class SearchLogRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/logs/search_logs.jsonl';
    }

    public function create(array $data): bool
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $line = json_encode($data, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        return file_put_contents($this->file, $line, FILE_APPEND) !== false;
    }
}
