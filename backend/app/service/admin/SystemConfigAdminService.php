<?php

namespace app\service\admin;

use app\exception\BusinessException;
use app\repository\mysql\SystemConfigRepository;
use support\Pagination;

class SystemConfigAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['page' => 1, 'page_size' => 20];
        $repo = new SystemConfigRepository();
        $total = $repo->countAll();
        $list = $repo->findPage((int) $query['page'], (int) $query['page_size']);
        return Pagination::format($list, $total, (int) $query['page'], (int) $query['page_size']);
    }

    public function update(string $key, string $value): array
    {
        $row = (new SystemConfigRepository())->updateByKey($key, $value);
        if (empty($row)) {
            throw new BusinessException('配置项不存在', 40001);
        }
        return [
            'success' => true,
            'action' => 'update',
            'id' => $row['id'] ?? null,
            'data' => $row,
        ];
    }
}
