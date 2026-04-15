<?php

namespace app\controller\admin;

use app\repository\mysql\PermissionRepository;
use support\ApiResponse;
use support\Pagination;

class PermissionController
{
    public function index(): array
    {
        $list = (new PermissionRepository())->all();
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }
}
