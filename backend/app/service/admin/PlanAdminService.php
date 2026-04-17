<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\repository\mysql\SubscriptionRepository;

class PlanAdminService
{
    public function getList(array $query = []): array
    {
        $page = $query['page'] ?? 1;
        $pageSize = $query['page_size'] ?? 20;
        $list = [];
        $current = (new SubscriptionRepository())->findCurrentByUserId(1);
        if (!empty($current)) {
            $list[] = $current;
        }
        return AdminListBuilder::make($list, $page, $pageSize);
    }
}
