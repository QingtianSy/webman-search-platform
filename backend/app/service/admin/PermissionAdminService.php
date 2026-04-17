<?php

namespace app\service\admin;

use app\repository\mysql\PermissionRepository;
use support\Pagination;

class PermissionAdminService
{
    public function getList(): array
    {
        $list = (new PermissionRepository())->all();
        return Pagination::format($list, count($list), 1, 20);
    }
}
