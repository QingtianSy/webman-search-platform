<?php

namespace app\repository\mysql;

use app\validate\user\ApiSourceValidate;
use PDO;
use support\adapter\MySqlClient;

class UserApiSourceRepository
{
    private const COLUMNS = 'id, user_id, name, method, url, keyword_param, keyword_position, type_param, type_position, option_delimiter, option_format, headers, extra_config, data_path, success_code_field, success_code_value, timeout, sort_order, status, remark, created_at, updated_at';
    private const LIST_COLUMNS = 'id, user_id, name, method, url, keyword_param, keyword_position, type_param, type_position, option_delimiter, option_format, data_path, success_code_field, success_code_value, timeout, sort_order, status, remark, created_at, updated_at';

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

            $stmt = $pdo->prepare('SELECT ' . self::LIST_COLUMNS . ' FROM user_api_sources WHERE user_id = :user_id ORDER BY sort_order ASC, id DESC LIMIT :offset, :limit');
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

    // 管理/用户后台 CRUD 用：数据源故障（PDO 不可用/异常）直接抛出，记录不存在返回 []。
    // 配合 service 层把 50001 与 40004 分流，避免 Question 那类"Mongo 挂 → 误报不存在"。
    public function findByIdStrict(int $userId, int $id): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        $stmt = $pdo->prepare('SELECT ' . self::COLUMNS . ' FROM user_api_sources WHERE id = :id AND user_id = :user_id LIMIT 1');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(int $userId, array $data): int
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return 0;
        }
        try {
            return $this->insertRow($pdo, $userId, $data);
        } catch (\PDOException $e) {
            error_log("[UserApiSourceRepository] create failed: " . $e->getMessage());
            return 0;
        }
    }

    // strict: PDO 不可用/异常抛出；正常路径保证返回 lastInsertId > 0。
    public function createStrict(int $userId, array $data): int
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        $id = $this->insertRow($pdo, $userId, $data);
        if ($id <= 0) {
            throw new \RuntimeException('insert returned no id');
        }
        return $id;
    }

    private function insertRow(PDO $pdo, int $userId, array $data): int
    {
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
    }

    public function update(int $userId, int $id, array $data): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }
        $sets = self::buildUpdateSets($data, $bind);
        if (empty($sets)) {
            return false;
        }
        $bind['id'] = $id;
        $bind['user_id'] = $userId;
        $sql = 'UPDATE user_api_sources SET ' . implode(', ', $sets) . ' WHERE id = :id AND user_id = :user_id';
        try {
            $check = $pdo->prepare('SELECT id FROM user_api_sources WHERE id = :id AND user_id = :user_id');
            $check->execute(['id' => $id, 'user_id' => $userId]);
            if (!$check->fetch()) {
                return false;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($bind);
            return true;
        } catch (\PDOException $e) {
            error_log("[UserApiSourceRepository] update failed: " . $e->getMessage());
            return false;
        }
    }

    // strict: PDO 不可用/异常抛出；返回值语义同 update：true=已更新，false=记录不存在或无可更新字段。
    public function updateStrict(int $userId, int $id, array $data): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        $sets = self::buildUpdateSets($data, $bind);
        if (empty($sets)) {
            return false;
        }
        $bind['id'] = $id;
        $bind['user_id'] = $userId;
        $check = $pdo->prepare('SELECT id FROM user_api_sources WHERE id = :id AND user_id = :user_id');
        $check->execute(['id' => $id, 'user_id' => $userId]);
        if (!$check->fetch()) {
            return false;
        }
        $stmt = $pdo->prepare('UPDATE user_api_sources SET ' . implode(', ', $sets) . ' WHERE id = :id AND user_id = :user_id');
        $stmt->execute($bind);
        return true;
    }

    private static function buildUpdateSets(array $data, ?array &$bind): array
    {
        $allowed = ['name', 'method', 'url', 'keyword_param', 'keyword_position', 'type_param', 'type_position', 'option_delimiter', 'option_format', 'headers', 'extra_config', 'data_path', 'success_code_field', 'success_code_value', 'timeout', 'sort_order', 'status', 'remark'];
        $sets = [];
        $bind = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = "{$field} = :{$field}";
                $bind[$field] = $data[$field];
            }
        }
        if (!empty($sets)) {
            $sets[] = 'updated_at = NOW()';
        }
        return $sets;
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

    // strict: PDO 不可用/异常抛出；false 仅代表"记录不存在"（rowCount=0）。
    public function deleteStrict(int $userId, int $id): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        $stmt = $pdo->prepare('DELETE FROM user_api_sources WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        return $stmt->rowCount() > 0;
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

    // 严格版本：DB 故障抛 RuntimeException，避免"启用源因 DB 挂了返空 → 搜索主链路静默跳过第三方源 → 用户体验到'没命中' 却没法排查"。
    public function findActiveByUserIdStrict(int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $stmt = $pdo->prepare('SELECT ' . self::COLUMNS . ' FROM user_api_sources WHERE user_id = :user_id AND status = 1 ORDER BY sort_order ASC, id ASC');
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            throw new \RuntimeException('api source active list failed: ' . $e->getMessage(), 0, $e);
        }
    }

    // 严格版本：DB 故障抛 RuntimeException，避免"列表因 DB 挂了返空 → 前端以为用户没配置过任何源"。
    public function findByUserIdStrict(int $userId, array $query = []): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        $page = max(1, (int) ($query['page'] ?? 1));
        $pageSize = max(1, min(100, (int) ($query['page_size'] ?? 20)));
        $offset = ($page - 1) * $pageSize;

        try {
            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM user_api_sources WHERE user_id = :user_id');
            $countStmt->execute(['user_id' => $userId]);
            $total = (int) $countStmt->fetchColumn();

            $stmt = $pdo->prepare('SELECT ' . self::LIST_COLUMNS . ' FROM user_api_sources WHERE user_id = :user_id ORDER BY sort_order ASC, id DESC LIMIT :offset, :limit');
            $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue('limit', $pageSize, PDO::PARAM_INT);
            $stmt->execute();
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return ['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize];
        } catch (\PDOException $e) {
            throw new \RuntimeException('api source list failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function test(int $userId, int $id): array
    {
        // 之前用非严格 findById：MySQL 抖动 → [] → 一律返回"接口源不存在"。
        // 同步 controller 收到这条就 4xx，异步 Timer 也会把"不存在"落进 test result 缓存误导管理员。
        // 改用 strict + infra 标记：记录确实不存在 → 空数组 → 维持原文案；DB 故障 → infra=true 让上层翻 50001。
        try {
            $row = $this->findByIdStrict($userId, $id);
        } catch (\RuntimeException $e) {
            error_log("[UserApiSourceRepository] test lookup failed id={$id}: " . $e->getMessage());
            return [
                'id' => $id,
                'status' => 'error',
                'message' => '数据源暂不可用，请稍后重试',
                'infra' => true,
                'tested_at' => date('Y-m-d H:i:s'),
            ];
        }
        if (!$row) {
            return ['id' => $id, 'status' => 'error', 'message' => '接口源不存在'];
        }
        $url = $row['url'] ?? '';
        if ($url === '') {
            return ['id' => $id, 'status' => 'error', 'message' => 'URL为空', 'tested_at' => date('Y-m-d H:i:s')];
        }
        try {
            $resolved = ApiSourceValidate::resolveToSafeIp($url);
        } catch (\Throwable $e) {
            return ['id' => $id, 'status' => 'error', 'message' => 'URL安全检查失败: ' . $e->getMessage(), 'tested_at' => date('Y-m-d H:i:s')];
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
            // 不要把 Guzzle/cURL 原文 $e->getMessage() 回显：它会带目标 URL、Basic Auth、解析后的内网 IP 等；
            // 配合 test-result 能被泄露 task_id 越权读到的场景，会进一步放大。原文仅落服务端日志。
            error_log("[UserApiSourceRepository] test http failed id={$id}: " . $e->getMessage());
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
