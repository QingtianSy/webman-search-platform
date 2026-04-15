<?php

namespace app\controller\user;

use app\repository\mysql\CollectAccountRepository;
use app\repository\mysql\CollectTaskRepository;
use support\ApiResponse;
use support\Pagination;

class CollectController
{
    public function accounts(): array
    {
        $list = (new CollectAccountRepository())->listByUserId(1);
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }

    public function tasks(): array
    {
        $list = (new CollectTaskRepository())->listByUserId(1);
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }
}
