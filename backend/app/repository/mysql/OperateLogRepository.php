<?php

namespace app\repository\mysql;

class OperateLogRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/operate_logs.json';
    }

    public function listByUserId(int $userId): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        $rows = is_array($rows) ? $rows : [];
        return array_values(array_filter($rows, fn ($row) => (int) ($row['user_id'] ?? 0) === $userId));
    }
}
