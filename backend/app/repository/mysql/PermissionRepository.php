<?php

namespace app\repository\mysql;

/**
 * PermissionRepository
 *
 * 当前阶段：
 * - 从 storage/mock/permissions.json 读取权限
 *
 * 真接入阶段：
 * - 替换为 MySQL permissions 表查询
 */
class PermissionRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/permissions.json';
    }

    public function all(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        return is_array($rows) ? $rows : [];
    }
}
