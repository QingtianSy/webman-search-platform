<?php

namespace app\repository\mysql;

use app\validate\user\ApiSourceValidate;
use PDO;
use support\adapter\MySqlClient;

class ApiSourceRepository
{
    public function all(): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->query('SELECT id, name, code, method, url, timeout, retry_times, status, success_code_field, success_code_value, data_path, remark, created_at, updated_at FROM api_sources ORDER BY id DESC LIMIT 10000');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[ApiSourceRepository] all failed: " . $e->getMessage());
            return [];
        }
    }

    public function countAll(): int
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return 0;
        }
        try {
            return (int) $pdo->query('SELECT COUNT(*) FROM api_sources')->fetchColumn();
        } catch (\PDOException $e) {
            error_log("[ApiSourceRepository] countAll failed: " . $e->getMessage());
            return 0;
        }
    }

    public function findPage(int $page, int $pageSize): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $offset = ($page - 1) * $pageSize;
            $stmt = $pdo->prepare('SELECT id, name, code, method, url, timeout, retry_times, status, success_code_field, success_code_value, data_path, remark, created_at, updated_at FROM api_sources ORDER BY id DESC LIMIT :limit OFFSET :offset');
            $stmt->bindValue('limit', $pageSize, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[ApiSourceRepository] findPage failed: " . $e->getMessage());
            return [];
        }
    }

    public function findById(int $id): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('SELECT id, name, code, method, url, timeout, retry_times, status, success_code_field, success_code_value, data_path, remark, created_at, updated_at FROM api_sources WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[ApiSourceRepository] findById failed: " . $e->getMessage());
            return [];
        }
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
            $resolved = ApiSourceValidate::resolveHost($url, false);
        } catch (\Throwable $e) {
            return ['id' => $id, 'status' => 'error', 'message' => 'URL解析失败: ' . $e->getMessage(), 'tested_at' => date('Y-m-d H:i:s')];
        }
        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => (int) ($row['timeout'] ?? 10),
                'verify' => true,
                'allow_redirects' => false,
                'curl' => [
                    CURLOPT_RESOLVE => ["{$resolved['host']}:{$resolved['port']}:{$resolved['ip']}"],
                ],
            ]);
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
}
