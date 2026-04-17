<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\repository\mysql\PermissionRepository;

class PermissionAdminService
{
    public function getList(array $query): array
    {
        $list = (new PermissionRepository())->all();
        if ($query['keyword'] !== '') {
            $list = array_values(array_filter($list, function ($row) use ($query) {
                return str_contains((string) ($row['code'] ?? ''), $query['keyword'])
                    || str_contains((string) ($row['name'] ?? ''), $query['keyword']);
            }));
        }
        return AdminListBuilder::make($list, $query['page'], $query['page_size']);
    }
}
