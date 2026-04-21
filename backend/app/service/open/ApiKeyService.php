<?php

namespace app\service\open;

use app\repository\mysql\ApiKeyRepository;
use app\repository\mysql\UserRepository;
use PDO;
use support\adapter\MySqlClient;

class ApiKeyService
{
    // verify() 语义：
    //   null        = 凭证无效（API Key 不存在 / 已失效 / secret 不匹配 / 关联用户禁用）
    //   抛 RuntimeException = 基础设施故障（MySQL 不可用）
    // 之前 findByApiKey / UserRepository::findById 都是非严格，DB 故障会被 verify 误判成"凭证无效"，
    // 中间件给客户端 40008 "API Key 无效"；合法调用方看到莫名其妙的鉴权失败，既查不出问题也无法触发告警。
    // 改为 Strict：DB 故障抛 RuntimeException，由中间件翻译为 50001 "鉴权服务暂不可用"。
    public function verify(?string $apiKey, ?string $apiSecret): ?array
    {
        if (empty($apiKey) || empty($apiSecret)) {
            return null;
        }
        $record = (new ApiKeyRepository())->findByApiKeyStrict($apiKey);
        if (!$record) {
            return null;
        }
        if ((int) ($record['status'] ?? 0) !== 1) {
            return null;
        }
        if (!empty($record['expire_at']) && strtotime($record['expire_at']) < time()) {
            return null;
        }
        $userId = (int) ($record['user_id'] ?? 0);
        if ($userId > 0) {
            $user = (new UserRepository())->findByIdStrict($userId);
            if (!$user || (int) ($user['status'] ?? 0) !== 1) {
                return null;
            }
        }

        // 只认 bcrypt 哈希。明文列 api_secret 已弃用，存在的话只会是历史数据或误写；
        // 不再做 hash_equals 兜底，避免 DB dump / error_log / replica 同步环节把明文密钥泄出。
        // 旧记录若 api_secret_hash 为空，视为失效，强制用户走 create 重新发放。
        if (empty($record['api_secret_hash'])) {
            error_log("[OpenApiKeyService] api_secret_hash missing for api_key id=" . ($record['id'] ?? '?') . ", rejecting");
            return null;
        }
        return password_verify($apiSecret, $record['api_secret_hash']) ? $record : null;
    }

    public function listByUserId(int $userId): array
    {
        return (new ApiKeyRepository())->findByUserId($userId);
    }

    public function detailById(int $userId, int $id): array
    {
        return (new ApiKeyRepository())->findByIdAndUserId($id, $userId);
    }

    public function create(int $userId, string $appName): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        $apiKey = 'ak_' . bin2hex(random_bytes(16));
        $apiSecret = 'sk_' . bin2hex(random_bytes(24));
        $secretHash = password_hash($apiSecret, PASSWORD_BCRYPT);
        try {
            $stmt = $pdo->prepare('INSERT INTO user_api_keys (user_id, app_name, api_key, api_secret_hash, status, created_at, updated_at) VALUES (:user_id, :app_name, :api_key, :api_secret_hash, 1, NOW(), NOW())');
            $stmt->execute([
                'user_id' => $userId,
                'app_name' => $appName !== '' ? $appName : '新应用',
                'api_key' => $apiKey,
                'api_secret_hash' => $secretHash,
            ]);
            return [
                'id' => (int) $pdo->lastInsertId(),
                'user_id' => $userId,
                'app_name' => $appName,
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        } catch (\PDOException $e) {
            error_log("[OpenApiKeyService] create failed: " . $e->getMessage());
            return [];
        }
    }

    public function delete(int $userId, int $id): bool
    {
        return (new ApiKeyRepository())->delete($userId, $id);
    }
}
