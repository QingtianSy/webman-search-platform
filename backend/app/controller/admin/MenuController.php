<?php

namespace app\controller\admin;

use app\repository\mysql\MenuRepository;
use support\ApiResponse;
use support\Pagination;

class MenuController
{
    public function index(): array
    {
        $list = (new MenuRepository())->all();
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }
}
