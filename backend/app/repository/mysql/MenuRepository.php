<?php

namespace app\repository\mysql;

use support\adapter\MySqlClient;

/**
 * MenuRepository
 */
class MenuRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/menus.json';
    }

    public function all(): array
    {
        return config('integration.auth_rbac_source', 'mock') === 'real'
            ? $this->allReal()
            : $this->allMock();
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
         * SELECT id, parent_id, name, path, permission_code, sort, status
         * FROM menus
         * WHERE status = 1
         * ORDER BY sort ASC, id ASC;
         */
        return [];
    }
}
