<?php

namespace app\repository\mysql;

use support\adapter\MySqlClient;

/**
 * RolePermissionRepository
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
        return config('integration.auth_rbac_source', 'mock') === 'real'
            ? $this->permissionCodesByRoleIdsReal($roleIds)
            : $this->permissionCodesByRoleIdsMock($roleIds);
    }

    protected function permissionCodesByRoleIdsMock(array $roleIds): array
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

    protected function permissionCodesByRoleIdsReal(array $roleIds): array
    {
        if (!MySqlClient::isConfigured()) {
            return [];
        }

        /**
         * 未来真实查询示意：
         * SELECT p.code
         * FROM role_permission rp
         * INNER JOIN permissions p ON p.id = rp.permission_id
         * WHERE rp.role_id IN (...);
         */
        return [];
    }
}
