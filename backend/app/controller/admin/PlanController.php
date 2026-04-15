<?php

namespace app\controller\admin;

use support\ApiResponse;
use support\Pagination;

class PlanController
{
    public function index(): array
    {
        $list = [(new \app\repository\mysql\SubscriptionRepository())->findCurrentByUserId(1)];
        return ApiResponse::success(Pagination::format(array_filter($list), count(array_filter($list)), 1, 20));
    }
}
