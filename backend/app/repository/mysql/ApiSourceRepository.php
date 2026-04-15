<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class ApiSourceRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/api_sources.json';
    }

    public function all(): array
    {
        return config('integration.api_source_source', 'mock') === 'real'
            ? $this->allReal()
            : $this->allMock();
    }

    public function findById(int $id): array
    {
        return config('integration.api_source_source', 'mock') === 'real'
            ? $this->findByIdReal($id)
            : $this->findByIdMock($id);
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

    protected function allMock(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        return is_array($rows) ? $rows : [];
    }

    protected function findByIdMock(int $id): array
    {
        foreach ($this->allMock() as $row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                return $row;
            }
        }
        return [];
    }

    protected function allReal(): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        $stmt = $pdo->query('SELECT id, name, code, method, url, timeout, retry_times, status, success_code_field, success_code_value, data_path, remark, created_at, updated_at FROM api_sources ORDER BY id DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    protected function findByIdReal(int $id): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        $stmt = $pdo->prepare('SELECT id, name, code, method, url, timeout, retry_times, status, success_code_field, success_code_value, data_path, remark, created_at, updated_at FROM api_sources WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}
