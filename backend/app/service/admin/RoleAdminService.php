<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\repository\mysql\RoleRepository;

class RoleAdminService
{
    public function getList(int $page = 1, int $pageSize = 20): array
    {
        $list = (new RoleRepository())->all();
        return AdminListBuilder::make($list, $page, $pageSize);
    }
}
