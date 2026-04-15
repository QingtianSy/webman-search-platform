<?php

namespace app\repository\mysql;

class CollectTaskDetailRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/collect_task_details.json';
    }

    public function findByTaskNo(string $taskNo): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        $rows = is_array($rows) ? $rows : [];
        foreach ($rows as $row) {
            if (($row['task_no'] ?? '') === $taskNo) {
                return $row;
            }
        }
        return [];
    }
}
