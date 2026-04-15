<?php

namespace app\service\auth;

/**
 * PasswordService
 *
 * 当前阶段：
 * - 兼容 mock users.json 中的明文 password
 *
 * 后续阶段：
 * - 统一切换为 password_hash/password_verify 方案
 * - 控制 AuthService 不直接关心密码存储格式
 */
class PasswordService
{
    public function verify(string $plain, array $user): bool
    {
        if (isset($user['password_hash']) && $user['password_hash'] !== '') {
            return password_verify($plain, (string) $user['password_hash']);
        }
        return ($user['password'] ?? '') === $plain;
    }
}
