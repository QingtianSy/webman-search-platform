<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\repository\mysql\MenuRepository;

class MenuAdminService
{
    public function getList(): array
    {
        $list = (new MenuRepository())->all();
        return AdminListBuilder::make($list);
    }
}
