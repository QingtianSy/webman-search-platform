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
        $url = $row['url'] ?? '';
        if ($url === '') {
            return ['id' => $id, 'status' => 'error', 'message' => 'URL为空', 'tested_at' => date('Y-m-d H:i:s')];
        }
        try {
            $client = new \GuzzleHttp\Client(['timeout' => (int) ($row['timeout'] ?? 10), 'verify' => false]);
            $method = strtoupper($row['method'] ?? 'GET');
            $response = $client->request($method, $url);
            $code = $response->getStatusCode();
            return [
                'id' => $id,
                'status' => $code >= 200 && $code < 400 ? 'success' : 'error',
                'message' => "HTTP {$code}",
                'tested_at' => date('Y-m-d H:i:s'),
            ];
        } catch (\Throwable $e) {
            return [
                'id' => $id,
                'status' => 'error',
                'message' => $e->getMessage(),
                'tested_at' => date('Y-m-d H:i:s'),
            ];
        }
    }

    protected function allMock(): array
    {
        if (!is_file($this->file)) {
            error_log("[ApiSourceRepository] Mock file not found: {$this->file}");
            return [];
        }
        
        $content = @file_get_contents($this->file);
        if ($content === false) {
            error_log("[ApiSourceRepository] Failed to read mock file: {$this->file}");
            return [];
        }
        
        $rows = json_decode($content, true);
        if (!is_array($rows)) {
            error_log("[ApiSourceRepository] Invalid JSON in mock file: {$this->file}");
            return [];
        }
        
        return $rows;
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
            error_log("[ApiSourceRepository] Database connection failed");
            return [];
        }
        
        try {
            $stmt = $pdo->query('SELECT id, name, code, method, url, timeout, retry_times, status, success_code_field, success_code_value, data_path, remark, created_at, updated_at FROM api_sources ORDER BY id DESC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[ApiSourceRepository] Query failed: " . $e->getMessage());
            return [];
        }
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
