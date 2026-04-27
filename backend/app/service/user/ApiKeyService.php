<?php

namespace app\service\user;

use app\exception\BusinessException;
use app\repository\mysql\ApiKeyRepository;
use PDO;
use support\adapter\MySqlClient;
use support\Db;

class ApiKeyService
{
    public function listByUserId(int $userId): array
    {
        try {
            return (new ApiKeyRepository())->findByUserIdStrict($userId);
        } catch (\RuntimeException $e) {
            throw new BusinessException('API Key 服务暂不可用，请稍后重试', 50001);
        }
    }

    // 列表分页严格化：之前 Controller 直接用 countByUserId/findPageByUserId，
    // DB 故障时会被翻译成 total=0 + 空列表 + 200，前端看起来像"你还没创建 key"，
    // 用户可能误以为先前创建的 key 被清空从而重复创建。统一走 Strict + 50001。
    public function listPaginated(int $userId, int $page, int $pageSize): array
    {
        try {
            $repo = new ApiKeyRepository();
            $total = $repo->countByUserIdStrict($userId);
            $list = $repo->findPageByUserIdStrict($userId, $page, $pageSize);
            return ['total' => $total, 'list' => $list];
        } catch (\RuntimeException $e) {
            throw new BusinessException('API Key 服务暂不可用，请稍后重试', 50001);
        }
    }

    public function detailById(int $userId, int $id): array
    {
        try {
            return (new ApiKeyRepository())->findByIdAndUserIdStrict($id, $userId);
        } catch (\Throwable $e) {
            error_log("[ApiKeyService] detailById failed: " . $e->getMessage());
            throw new BusinessException('API Key 查询暂不可用，请稍后重试', 50001);
        }
    }

    public function create(int $userId, string $appName): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new BusinessException('数据库不可用，创建 API Key 失败', 50001);
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
            // 之前这里返回 []，Controller 不判就记操作日志并回 200，出现"接口成功但 key 没入库"。
            // 统一改成抛 BusinessException，由全局异常处理落到 5xx 响应，调用方不会误以为成功。
            error_log("[ApiKeyService] create failed: " . $e->getMessage());
            throw new BusinessException('创建 API Key 失败，请稍后重试', 50001);
        }
    }

    public function toggle(int $userId, int $id, int $status): bool
    {
        try {
            $ok = (new ApiKeyRepository())->toggleStrict($userId, $id, $status);
        } catch (\RuntimeException $e) {
            // 之前非严格版本 DB 故障 → false → 40001 "API Key 不存在"，会误导用户以为 key 已被他人删除。
            throw new BusinessException('API Key 服务暂不可用，请稍后重试', 50001);
        }
        if (!$ok) {
            throw new BusinessException('API Key 不存在', 40001);
        }
        return true;
    }

    public function delete(int $userId, int $id): bool
    {
        try {
            $ok = (new ApiKeyRepository())->deleteStrict($userId, $id);
        } catch (\RuntimeException $e) {
            throw new BusinessException('API Key 服务暂不可用，请稍后重试', 50001);
        }
        if (!$ok) {
            throw new BusinessException('API Key 不存在', 40001);
        }
        return true;
    }

    // 设为默认：单事务把同用户其它 key 的 is_default=0 + 当前 id=1；id 归属本人校验失败抛 40001。
    // 依赖迁移 0023 给 user_api_keys 添加了 is_default 列。
    public function setDefault(int $userId, int $id): bool
    {
        try {
            return Db::transaction(function () use ($userId, $id) {
                $target = Db::table('user_api_keys')->where('id', $id)->where('user_id', $userId)->first();
                if (!$target) {
                    throw new BusinessException('API Key 不存在', 40001);
                }
                Db::table('user_api_keys')->where('user_id', $userId)->where('id', '!=', $id)->update(['is_default' => 0]);
                Db::table('user_api_keys')->where('id', $id)->update(['is_default' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
                return true;
            });
        } catch (BusinessException $e) {
            throw $e;
        } catch (\Throwable $e) {
            error_log("[ApiKeyService] setDefault failed: " . $e->getMessage());
            throw new BusinessException('API Key 服务暂不可用，请稍后重试', 50001);
        }
    }

    // 重新生成 secret：返回明文一次，DB 只存 bcrypt hash。api_key 不变，避免客户端需要重新配置公钥侧。
    public function regenerate(int $userId, int $id): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new BusinessException('API Key 服务暂不可用，请稍后重试', 50001);
        }
        $check = $pdo->prepare('SELECT id, api_key, app_name FROM user_api_keys WHERE id = :id AND user_id = :uid LIMIT 1');
        $check->execute(['id' => $id, 'uid' => $userId]);
        $row = $check->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new BusinessException('API Key 不存在', 40001);
        }
        $newSecret = 'sk_' . bin2hex(random_bytes(24));
        $newHash = password_hash($newSecret, PASSWORD_BCRYPT);
        try {
            $stmt = $pdo->prepare('UPDATE user_api_keys SET api_secret_hash = :h, updated_at = NOW() WHERE id = :id AND user_id = :uid');
            $stmt->execute(['h' => $newHash, 'id' => $id, 'uid' => $userId]);
        } catch (\PDOException $e) {
            error_log("[ApiKeyService] regenerate failed: " . $e->getMessage());
            throw new BusinessException('重置 Secret 失败，请稍后重试', 50001);
        }
        return [
            'id' => (int) $row['id'],
            'api_key' => $row['api_key'],
            'api_secret' => $newSecret,
            'app_name' => $row['app_name'],
        ];
    }
}
