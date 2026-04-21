<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class UserRepository
{
    public function all(): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->query('SELECT id, username, nickname, email, mobile, avatar, status, created_at, updated_at FROM users ORDER BY id DESC LIMIT 10000');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[UserRepository] all failed: " . $e->getMessage());
            return [];
        }
    }

    public function findByUsername(string $username): ?array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return null;
        }
        try {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
            $stmt->execute(['username' => $username]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            error_log("[UserRepository] findByUsername failed: " . $e->getMessage());
            return null;
        }
    }

    public function findById(int $id): ?array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return null;
        }
        try {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            error_log("[UserRepository] findById failed: " . $e->getMessage());
            return null;
        }
    }

    public function updateProfile(int $id, array $data): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }
        $allowed = ['nickname', 'email', 'mobile', 'avatar'];
        $sets = [];
        $bind = ['id' => $id];
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
        $sql = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id';
        try {
            $check = $pdo->prepare('SELECT id FROM users WHERE id = :id');
            $check->execute(['id' => $id]);
            if (!$check->fetch()) {
                return false;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($bind);
            return true;
        } catch (\PDOException $e) {
            error_log("[UserRepository] updateProfile failed: " . $e->getMessage());
            return false;
        }
    }

    // 严格版本：DB 故障抛 RuntimeException，避免把"DB 挂了"伪装成"账号或密码错误 / 未登录"。
    public function findByUsernameStrict(string $username): ?array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
            $stmt->execute(['username' => $username]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            throw new \RuntimeException('user find failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function findByIdStrict(int $id): ?array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            throw new \RuntimeException('user find failed: ' . $e->getMessage(), 0, $e);
        }
    }

    // 严格版本：DB 故障抛 RuntimeException，false 专指"记录不存在/无可更新字段"。
    public function updateProfileStrict(int $id, array $data): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        $allowed = ['nickname', 'email', 'mobile', 'avatar'];
        $sets = [];
        $bind = ['id' => $id];
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
        $sql = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id';
        try {
            $check = $pdo->prepare('SELECT id FROM users WHERE id = :id');
            $check->execute(['id' => $id]);
            if (!$check->fetch()) {
                return false;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($bind);
            return true;
        } catch (\PDOException $e) {
            throw new \RuntimeException('user updateProfile failed: ' . $e->getMessage(), 0, $e);
        }
    }

    // 登录/注册链路把 sessions_invalidated_at 设为"新 token 的 iat_ms 对应的 DATETIME(3)"，
    // 让中间件 $invalidatedMs > $iatMs 对旧 token 成立、对新 token 不成立，实现"新登录挤掉旧登录"。
    // 不受 Redis 键丢失影响。
    // 失败必须显式抛错：吞掉 PDO 异常会导致调用方误认为"旧会话已作废"，中间件仍用旧 token 放行。
    // 调用方（AuthService::issueSessionToken）据此回滚 Redis 并向上抛 BusinessException。
    //
    // 使用 GREATEST 防止 invalidated_ms 倒退：
    //   - 同毫秒重复 logout：:t == 当前值 → rowCount=0，幂等成功；
    //   - 并发签发 A(t=100) 与 B(t=110)：B 先落 → A 执行 GREATEST(110,100)=110 → A token 自然作废，B 胜出；
    //     若直接 SET 则 A 会把列写回 100，让 B 之外的旧 token 复活。
    // COALESCE 是因为 sessions_invalidated_at 可能为 NULL（GREATEST(NULL, x)=NULL，会丢掉本次 bump）。
    public function bumpSessionInvalidatedAt(int $id, string $datetime3): void
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('[UserRepository] bumpSessionInvalidatedAt: MySQL unavailable');
        }
        $stmt = $pdo->prepare(
            "UPDATE users SET sessions_invalidated_at = GREATEST(COALESCE(sessions_invalidated_at, '1970-01-01 00:00:00.000'), :t) WHERE id = :id"
        );
        $stmt->execute(['t' => $datetime3, 'id' => $id]);
        if ($stmt->rowCount() === 0) {
            // PDO 默认 affected-rows 模式：rowCount=0 可能是 (a) :t <= 当前值（同毫秒/时钟回跳，幂等成功），
            // 或 (b) user 不存在。必须显式区分，否则把幂等重试翻成 50001。
            $check = $pdo->prepare('SELECT 1 FROM users WHERE id = :id');
            $check->execute(['id' => $id]);
            if (!$check->fetch()) {
                throw new \RuntimeException("[UserRepository] bumpSessionInvalidatedAt: user $id not found");
            }
        }
    }
}
