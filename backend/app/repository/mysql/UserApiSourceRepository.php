<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class UserApiSourceRepository
{
    private const COLUMNS = 'id, user_id, name, method, url, keyword_param, keyword_position, type_param, type_position, option_delimiter, option_format, headers, extra_config, data_path, success_code_field, success_code_value, timeout, sort_order, status, remark, created_at, updated_at';

    public function findByUserId(int $userId, array $query = []): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return ['list' => [], 'total' => 0];
        }
        $page = max(1, (int) ($query['page'] ?? 1));
        $pageSize = max(1, min(100, (int) ($query['page_size'] ?? 20)));
        $offset = ($page - 1) * $pageSize;

        try {
            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM user_api_sources WHERE user_id = :user_id');
            $countStmt->execute(['user_id' => $userId]);
            $total = (int) $countStmt->fetchColumn();

            $stmt = $pdo->prepare('SELECT ' . self::COLUMNS . ' FROM user_api_sources WHERE user_id = :user_id ORDER BY sort_order ASC, id DESC LIMIT :offset, :limit');
            $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue('limit', $pageSize, PDO::PARAM_INT);
            $stmt->execute();
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return ['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize];
        } catch (\PDOException $e) {
            error_log("[UserApiSourceRepository] findByUserId failed: " . $e->getMessage());
            return ['list' => [], 'total' => 0];
        }
    }

    public function findById(int $userId, int $id): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('SELECT ' . self::COLUMNS . ' FROM user_api_sources WHERE id = :id AND user_id = :user_id LIMIT 1');
            $stmt->execute(['id' => $id, 'user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[UserApiSourceRepository] findById failed: " . $e->getMessage());
            return [];
        }
    }

    public function create(int $userId, array $data): int
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return 0;
        }
        try {
            $stmt = $pdo->prepare('INSERT INTO user_api_sources (user_id, name, method, url, keyword_param, keyword_position, type_param, type_position, option_delimiter, option_format, headers, extra_config, data_path, success_code_field, success_code_value, timeout, sort_order, status, remark, created_at, updated_at) VALUES (:user_id, :name, :method, :url, :keyword_param, :keyword_position, :type_param, :type_position, :option_delimiter, :option_format, :headers, :extra_config, :data_path, :success_code_field, :success_code_value, :timeout, :sort_order, :status, :remark, NOW(), NOW())');
            $stmt->execute([
                'user_id' => $userId,
                'name' => $data['name'],
                'method' => $data['method'] ?? 'GET',
                'url' => $data['url'],
                'keyword_param' => $data['keyword_param'] ?? 'q',
                'keyword_position' => $data['keyword_position'] ?? 'url_param',
                'type_param' => $data['type_param'] ?? null,
                'type_position' => $data['type_position'] ?? 'url_param',
                'option_delimiter' => $data['option_delimiter'] ?? '###',
                'option_format' => $data['option_format'] ?? null,
                'headers' => $data['headers'] ?? null,
                'extra_config' => $data['extra_config'] ?? null,
                'data_path' => $data['data_path'] ?? 'data',
                'success_code_field' => $data['success_code_field'] ?? 'code',
                'success_code_value' => $data['success_code_value'] ?? '1',
                'timeout' => (int) ($data['timeout'] ?? 10),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'status' => (int) ($data['status'] ?? 1),
                'remark' => $data['remark'] ?? null,
            ]);
            return (int) $pdo->lastInsertId();
        } catch (\PDOException $e) {
            error_log("[UserApiSourceRepository] create failed: " . $e->getMessage());
            return 0;
        }
    }

    public function update(int $userId, int $id, array $data): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }
        $allowed = ['name', 'method', 'url', 'keyword_param', 'keyword_position', 'type_param', 'type_position', 'option_delimiter', 'option_format', 'headers', 'extra_config', 'data_path', 'success_code_field', 'success_code_value', 'timeout', 'sort_order', 'status', 'remark'];
        $sets = [];
        $bind = ['id' => $id, 'user_id' => $userId];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = "{$field} = :{$field}";
                $bind[$field] = $data[$field];
            }
        }
        if (empty($sets)) {
            return false;
        }
        $sets[] = 'updated_at = NOW()';
        $sql = 'UPDATE user_api_sources SET ' . implode(', ', $sets) . ' WHERE id = :id AND user_id = :user_id';
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($bind);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("[UserApiSourceRepository] update failed: " . $e->getMessage());
            return false;
        }
    }

    public function delete(int $userId, int $id): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $stmt = $pdo->prepare('DELETE FROM user_api_sources WHERE id = :id AND user_id = :user_id');
            $stmt->execute(['id' => $id, 'user_id' => $userId]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("[UserApiSourceRepository] delete failed: " . $e->getMessage());
            return false;
        }
    }

    public function findActiveByUserId(int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('SELECT ' . self::COLUMNS . ' FROM user_api_sources WHERE user_id = :user_id AND status = 1 ORDER BY sort_order ASC, id ASC');
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[UserApiSourceRepository] findActiveByUserId failed: " . $e->getMessage());
            return [];
        }
    }

    public function test(int $userId, int $id): array
    {
        $row = $this->findById($userId, $id);
        if (!$row) {
            return ['id' => $id, 'status' => 'error', 'message' => '接口源不存在'];
        }
        $url = $row['url'] ?? '';
        if ($url === '') {
            return ['id' => $id, 'status' => 'error', 'message' => 'URL为空', 'tested_at' => date('Y-m-d H:i:s')];
        }
        try {
            $client = new \GuzzleHttp\Client(['timeout' => (int) ($row['timeout'] ?? 10), 'verify' => true]);
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
