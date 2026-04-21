<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class ApiKeyRepository
{
    public function findByApiKey(string $apiKey): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('SELECT id, user_id, app_name, api_key, api_secret_hash, status, expire_at, created_at, updated_at FROM user_api_keys WHERE api_key = :api_key LIMIT 1');
            $stmt->execute(['api_key' => $apiKey]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[ApiKeyRepository] findByApiKey failed: " . $e->getMessage());
            return [];
        }
    }

    // 严格版本：DB 故障抛 RuntimeException；记录不存在仍返 []。
    // 用于 OpenAPI 鉴权链路：DB 挂了不能再伪装成"API Key 无效"（40008），否则合法调用方只能看到鉴权失败却毫无排查线索。
    public function findByApiKeyStrict(string $apiKey): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $stmt = $pdo->prepare('SELECT id, user_id, app_name, api_key, api_secret_hash, status, expire_at, created_at, updated_at FROM user_api_keys WHERE api_key = :api_key LIMIT 1');
            $stmt->execute(['api_key' => $apiKey]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            throw new \RuntimeException('api key lookup failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function findByUserId(int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('SELECT id, user_id, app_name, api_key, status, expire_at, created_at, updated_at FROM user_api_keys WHERE user_id = :user_id ORDER BY id DESC LIMIT 10000');
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[ApiKeyRepository] findByUserId failed: " . $e->getMessage());
            return [];
        }
    }

    public function countByUserId(int $userId): int
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return 0;
        }
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM user_api_keys WHERE user_id = :user_id');
            $stmt->execute(['user_id' => $userId]);
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log("[ApiKeyRepository] countByUserId failed: " . $e->getMessage());
            return 0;
        }
    }

    public function findPageByUserId(int $userId, int $page, int $pageSize): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $offset = ($page - 1) * $pageSize;
            $stmt = $pdo->prepare('SELECT id, user_id, app_name, api_key, status, expire_at, created_at, updated_at FROM user_api_keys WHERE user_id = :user_id ORDER BY id DESC LIMIT :limit OFFSET :offset');
            $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue('limit', $pageSize, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[ApiKeyRepository] findPageByUserId failed: " . $e->getMessage());
            return [];
        }
    }

    public function findByIdAndUserId(int $id, int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('SELECT id, user_id, app_name, api_key, status, expire_at, created_at, updated_at FROM user_api_keys WHERE id = :id AND user_id = :user_id LIMIT 1');
            $stmt->execute(['id' => $id, 'user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[ApiKeyRepository] findByIdAndUserId failed: " . $e->getMessage());
            return [];
        }
    }

    public function findByIdAndUserIdStrict(int $id, int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $stmt = $pdo->prepare('SELECT id, user_id, app_name, api_key, status, expire_at, created_at, updated_at FROM user_api_keys WHERE id = :id AND user_id = :user_id LIMIT 1');
            $stmt->execute(['id' => $id, 'user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[ApiKeyRepository] findByIdAndUserIdStrict failed: " . $e->getMessage());
            throw new \RuntimeException('api key query failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function delete(int $userId, int $id): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $stmt = $pdo->prepare('DELETE FROM user_api_keys WHERE id = :id AND user_id = :user_id');
            $stmt->execute(['id' => $id, 'user_id' => $userId]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("[ApiKeyRepository] delete failed: " . $e->getMessage());
            return false;
        }
    }

    // 严格版本：DB 故障抛 RuntimeException，rowCount()=0 专指"不存在/已被删除"。
    // 非严格 delete 把这两种情况都揉成 false，service 层只能统一翻成 40001，掩盖 DB 故障。
    public function deleteStrict(int $userId, int $id): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $stmt = $pdo->prepare('DELETE FROM user_api_keys WHERE id = :id AND user_id = :user_id');
            $stmt->execute(['id' => $id, 'user_id' => $userId]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new \RuntimeException('api key delete failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function toggle(int $userId, int $id, int $status): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $check = $pdo->prepare('SELECT id FROM user_api_keys WHERE id = :id AND user_id = :user_id');
            $check->execute(['id' => $id, 'user_id' => $userId]);
            if (!$check->fetch()) {
                return false;
            }
            $stmt = $pdo->prepare('UPDATE user_api_keys SET status = :status, updated_at = NOW() WHERE id = :id AND user_id = :user_id');
            $stmt->execute(['status' => $status, 'id' => $id, 'user_id' => $userId]);
            return true;
        } catch (\PDOException $e) {
            error_log("[ApiKeyRepository] toggle failed: " . $e->getMessage());
            return false;
        }
    }

    // 严格版本：DB 故障抛 RuntimeException，返回 false 专指"不存在"。
    public function toggleStrict(int $userId, int $id, int $status): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $check = $pdo->prepare('SELECT id FROM user_api_keys WHERE id = :id AND user_id = :user_id');
            $check->execute(['id' => $id, 'user_id' => $userId]);
            if (!$check->fetch()) {
                return false;
            }
            $stmt = $pdo->prepare('UPDATE user_api_keys SET status = :status, updated_at = NOW() WHERE id = :id AND user_id = :user_id');
            $stmt->execute(['status' => $status, 'id' => $id, 'user_id' => $userId]);
            return true;
        } catch (\PDOException $e) {
            throw new \RuntimeException('api key toggle failed: ' . $e->getMessage(), 0, $e);
        }
    }

    // 严格列表/翻页：DB 故障抛出，用户列表页不再用"空列表"掩盖故障。
    public function findByUserIdStrict(int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $stmt = $pdo->prepare('SELECT id, user_id, app_name, api_key, status, expire_at, created_at, updated_at FROM user_api_keys WHERE user_id = :user_id ORDER BY id DESC LIMIT 10000');
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            throw new \RuntimeException('api key list failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function countByUserIdStrict(int $userId): int
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM user_api_keys WHERE user_id = :user_id');
            $stmt->execute(['user_id' => $userId]);
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            throw new \RuntimeException('api key count failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function findPageByUserIdStrict(int $userId, int $page, int $pageSize): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $offset = ($page - 1) * $pageSize;
            $stmt = $pdo->prepare('SELECT id, user_id, app_name, api_key, status, expire_at, created_at, updated_at FROM user_api_keys WHERE user_id = :user_id ORDER BY id DESC LIMIT :limit OFFSET :offset');
            $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue('limit', $pageSize, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            throw new \RuntimeException('api key page failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
