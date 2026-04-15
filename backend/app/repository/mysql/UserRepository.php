<?php

namespace app\repository\mysql;

/**
 * UserRepository
 *
 * 当前阶段：
 * - 从 storage/mock/users.json 读取用户
 *
 * 真接入阶段：
 * - 替换为 MySQL users 表查询
 * - 方法签名尽量保持不变，减少 AuthService 改动
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
}
