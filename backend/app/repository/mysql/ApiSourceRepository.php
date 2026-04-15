<?php

namespace app\repository\mysql;

class ApiSourceRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/api_sources.json';
    }

    public function all(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        return is_array($rows) ? $rows : [];
    }

    public function findById(int $id): array
    {
        foreach ($this->all() as $row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                return $row;
            }
        }
        return [];
    }

    public function test(int $id): array
    {
        $row = $this->findById($id);
        if (!$row) {
            return [];
        }
        return [
            'id' => $id,
            'status' => 'success',
            'message' => '模拟测试成功',
            'tested_at' => date('Y-m-d H:i:s'),
        ];
    }
}
