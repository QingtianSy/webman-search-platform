<?php

namespace app\repository\mysql;

/**
 * RolePermissionRepository
 *
 * 当前阶段：
 * - 从 storage/mock/role_permissions.json 读取角色权限
 *
 * 真接入阶段：
 * - 替换为 MySQL role_permission + permissions 联表查询
 * - 当前 mock 返回 permission_code，未来可由 Repository 内部完成 code/id 映射
 */
class RolePermissionRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/role_permissions.json';
    }

    public function permissionCodesByRoleIds(array $roleIds): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        $rows = is_array($rows) ? $rows : [];
        $codes = [];
        foreach ($rows as $row) {
            if (in_array((int) ($row['role_id'] ?? 0), $roleIds, true)) {
                $codes[] = (string) ($row['permission_code'] ?? '');
            }
        }
        return array_values(array_unique(array_filter($codes)));
    }
}
