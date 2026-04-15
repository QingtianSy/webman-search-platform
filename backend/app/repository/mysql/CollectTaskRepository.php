<?php

namespace app\repository\mysql;

class CollectTaskRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/collect_tasks.json';
    }

    protected function allRows(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        return is_array($rows) ? $rows : [];
    }

    protected function saveAll(array $rows): void
    {
        file_put_contents($this->file, json_encode(array_values($rows), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    public function listByUserId(int $userId): array
    {
        return array_values(array_filter($this->allRows(), fn ($row) => (int) ($row['user_id'] ?? 0) === $userId));
    }

    public function updateStatus(string $taskNo, int $status, string $errorMessage = ''): array
    {
        $rows = $this->allRows();
        foreach ($rows as &$row) {
            if (($row['task_no'] ?? '') === $taskNo) {
                $row['status'] = $status;
                $row['error_message'] = $errorMessage;
                $this->saveAll($rows);
                return $row;
            }
        }
        return [];
    }
}
