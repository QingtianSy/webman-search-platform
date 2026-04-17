<?php

namespace app\service\admin;

use app\repository\mysql\RoleRepository;
use support\Pagination;

class RoleAdminService
{
    public function getList(): array
    {
        $list = (new RoleRepository())->all();
        return Pagination::format($list, count($list), 1, 20);
    }
}
