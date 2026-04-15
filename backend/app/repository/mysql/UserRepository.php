<?php

namespace app\repository\mysql;

/**
 * UserRepository
 *
 * 当前阶段：
 * - 默认从 storage/mock/users.json 读取用户
 *
 * 真接入阶段：
 * - 切换 AUTH_RBAC_SOURCE=real 后，改走真实 users 表查询
 */
class UserRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/users.json';
    }

    public function findByUsername(string $username): array
    {
        $source = config('integration.auth_rbac_source', 'mock');
        return $source === 'real'
            ? $this->findByUsernameReal($username)
            : $this->findByUsernameMock($username);
    }

    protected function findByUsernameMock(string $username): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        if (!is_array($rows)) {
            return [];
        }
        foreach ($rows as $row) {
            if (($row['username'] ?? '') === $username) {
                return $row;
            }
        }
        return [];
    }

    protected function findByUsernameReal(string $username): array
    {
        return [
            'id' => 0,
            'username' => $username,
            'password_hash' => '',
            'nickname' => '',
            'avatar' => '',
            'status' => 1,
        ];
    }
}
