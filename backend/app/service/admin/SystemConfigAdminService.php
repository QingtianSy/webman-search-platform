<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\repository\mysql\SystemConfigRepository;

class SystemConfigAdminService
{
    public function getList(array $query = []): array
    {
        $page = $query['page'] ?? 1;
        $pageSize = $query['page_size'] ?? 20;
        $list = (new SystemConfigRepository())->all();
        return AdminListBuilder::make($list, $page, $pageSize);
    }

    public function update(string $key, string $value): array
    {
        return (new SystemConfigRepository())->updateByKey($key, $value);
    }
}
