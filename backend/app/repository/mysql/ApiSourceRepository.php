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

    public function countAllStrict(): int
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            return (int) $pdo->query('SELECT COUNT(*) FROM api_sources')->fetchColumn();
        } catch (\PDOException $e) {
            throw new \RuntimeException('api source count failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function findPageStrict(int $page, int $pageSize): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $offset = ($page - 1) * $pageSize;
            $stmt = $pdo->prepare('SELECT id, name, code, method, url, timeout, retry_times, status, success_code_field, success_code_value, data_path, remark, created_at, updated_at FROM api_sources ORDER BY id DESC LIMIT :limit OFFSET :offset');
            $stmt->bindValue('limit', $pageSize, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            throw new \RuntimeException('api source page query failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function findByIdStrict(int $id): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $stmt = $pdo->prepare('SELECT id, name, code, method, url, timeout, retry_times, status, success_code_field, success_code_value, data_path, remark, created_at, updated_at FROM api_sources WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            throw new \RuntimeException('api source find failed: ' . $e->getMessage(), 0, $e);
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
        // 测试入口先严格查：DB 故障抛 RuntimeException 让 admin service 翻 50001，
        // 而不是伪装成"接口源不存在 / 测试失败"。
        $row = $this->findByIdStrict($id);
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
            // Guzzle/cURL 原文会带 URL、Basic Auth、解析后的 IP 等内网线索，仅记服务端日志。
            error_log("[ApiSourceRepository] test http failed id={$id}: " . $e->getMessage());
            return [
                'id' => $id,
                'status' => 'error',
                'message' => $this->classifyTestError($e),
                'tested_at' => date('Y-m-d H:i:s'),
            ];
        }
    }

    protected function classifyTestError(\Throwable $e): string
    {
        if ($e instanceof \GuzzleHttp\Exception\ConnectException) {
            return '连接失败（超时或目标不可达）';
        }
        if ($e instanceof \GuzzleHttp\Exception\TooManyRedirectsException) {
            return '重定向次数超限';
        }
        if ($e instanceof \GuzzleHttp\Exception\BadResponseException) {
            $resp = $e->getResponse();
            return $resp ? ('HTTP ' . $resp->getStatusCode()) : '响应异常';
        }
        if ($e instanceof \GuzzleHttp\Exception\RequestException) {
            $resp = $e->getResponse();
            return $resp ? ('HTTP ' . $resp->getStatusCode()) : '请求失败';
        }
        return '测试失败（详情见服务端日志）';
    }
}
