<?php

namespace app\repository\mysql;

/**
 * RoleRepository
 *
 * 当前阶段：
 * - 从 storage/mock/roles.json 读取角色
 *
 * 真接入阶段：
 * - 替换为 MySQL roles 表查询
 */
class RoleRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/roles.json';
    }

    public function all(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        return is_array($rows) ? $rows : [];
    }

    public function findByIds(array $ids): array
    {
        return array_values(array_filter($this->all(), fn ($row) => in_array((int) ($row['id'] ?? 0), $ids, true)));
    }
}
