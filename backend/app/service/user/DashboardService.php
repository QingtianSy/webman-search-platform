<?php

namespace app\service\user;

class DashboardService
{
    public function overview(int $userId): array
    {
        return [
            'balance' => '0.00',
            'current_plan' => [
                'name' => '无套餐',
                'is_unlimited' => 0,
                'remain_quota' => 1000,
                'expire_at' => null,
            ],
            'today_usage' => 0,
            'total_usage' => 0,
            'announcements' => [],
            'user_id' => $userId,
        ];
    }
}
