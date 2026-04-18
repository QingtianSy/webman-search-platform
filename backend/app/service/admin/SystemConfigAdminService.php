<?php

namespace app\service\admin;

use app\repository\mysql\SystemConfigRepository;

class SystemConfigAdminService
{
    public function getList(array $query = []): array
    {
        $list = (new SystemConfigRepository())->all();

        return [
            'list' => $list,
            'total' => count($list),
            'page' => 1,
            'page_size' => 20,
        ];
    }

    public function update(string $key, string $value): array
    {
        $row = (new SystemConfigRepository())->updateByKey($key, $value);
        return [
            'success' => true,
            'action' => 'update',
            'id' => $row['id'] ?? null,
            'data' => $row,
        ];
    }
}
