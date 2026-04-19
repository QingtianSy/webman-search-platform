<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\repository\mysql\SystemConfigRepository;

class SystemConfigAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['page' => 1, 'page_size' => 20];
        $list = (new SystemConfigRepository())->all();
        return AdminListBuilder::make($list, (int) $query['page'], (int) $query['page_size']);
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
