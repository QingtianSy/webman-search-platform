<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\repository\mysql\RoleRepository;

class RoleAdminService
{
    public function getList(): array
    {
        $list = (new RoleRepository())->all();
        return AdminListBuilder::make($list);
    }
}
