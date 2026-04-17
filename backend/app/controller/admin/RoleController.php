<?php

namespace app\controller\admin;

use app\repository\mysql\RoleRepository;
use support\ApiResponse;
use support\Pagination;

class RoleController
{
    public function index()
    {
        $list = (new RoleRepository())->all();
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }
}
