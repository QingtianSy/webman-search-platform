<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\repository\mysql\MenuRepository;

class MenuAdminService
{
    public function getList(int $page = 1, int $pageSize = 20): array
    {
        $list = (new MenuRepository())->all();
        return AdminListBuilder::make($list, $page, $pageSize);
    }
}
