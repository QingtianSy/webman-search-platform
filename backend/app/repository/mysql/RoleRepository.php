<?php

namespace app\repository\mysql;

use support\adapter\MySqlClient;

/**
 * RoleRepository
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
        return config('integration.auth_rbac_source', 'mock') === 'real'
            ? $this->allReal()
            : $this->allMock();
    }

    public function findByIds(array $ids): array
    {
        return array_values(array_filter($this->all(), fn ($row) => in_array((int) ($row['id'] ?? 0), $ids, true)));
    }

    protected function allMock(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        return is_array($rows) ? $rows : [];
    }

    protected function allReal(): array
    {
        if (!MySqlClient::isConfigured()) {
            return [];
        }

        /**
         * 未来真实查询示意：
         * SELECT id, name, code, sort, status, created_at, updated_at
         * FROM roles
         * WHERE status = 1;
         */
        return [];
    }
}
