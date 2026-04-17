<?php

namespace app\service\admin;

use app\repository\mysql\MenuRepository;
use support\Pagination;

class MenuAdminService
{
    public function getList(): array
    {
        $list = (new MenuRepository())->all();
        return Pagination::format($list, count($list), 1, 20);
    }
}
