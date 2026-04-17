<?php

namespace app\service\admin;

use app\repository\mysql\SubscriptionRepository;
use app\common\admin\AdminListBuilder;

class PlanAdminService
{
    public function getList(): array
    {
        $list = [];
        $current = (new SubscriptionRepository())->findCurrentByUserId(1);
        if (!empty($current)) {
            $list[] = $current;
        }
        return AdminListBuilder::make($list);
    }
}
