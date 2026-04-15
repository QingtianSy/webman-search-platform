<?php

namespace app\repository\mysql;

/**
 * UserRoleRepository
 *
 * 当前阶段：
 * - 从 storage/mock/user_roles.json 读取用户角色关联
 *
 * 真接入阶段：
 * - 替换为 MySQL user_role 表查询
 */
class UserRoleRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/user_roles.json';
    }

    public function roleIdsByUserId(int $userId): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        $rows = is_array($rows) ? $rows : [];
        $ids = [];
        foreach ($rows as $row) {
            if ((int) ($row['user_id'] ?? 0) === $userId) {
                $ids[] = (int) ($row['role_id'] ?? 0);
            }
        }
        return $ids;
    }
}
