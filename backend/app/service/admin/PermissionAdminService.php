<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\repository\mysql\PermissionRepository;

class PermissionAdminService
{
    public function getList(): array
    {
        $list = (new PermissionRepository())->all();
        return AdminListBuilder::make($list);
    }
}
