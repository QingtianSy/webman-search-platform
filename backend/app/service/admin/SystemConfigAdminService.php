<?php

namespace app\service\admin;

use app\repository\mysql\SystemConfigRepository;
use app\common\admin\AdminListBuilder;

class SystemConfigAdminService
{
    public function getList(): array
    {
        $list = (new SystemConfigRepository())->all();
        return AdminListBuilder::make($list);
    }

    public function update(string $key, string $value): array
    {
        return (new SystemConfigRepository())->updateByKey($key, $value);
    }
}
