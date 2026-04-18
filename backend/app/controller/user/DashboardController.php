<?php

namespace app\controller\user;

class DashboardController
{
    public function overview()
    {
        return json([
            'code' => 1,
            'msg' => 'success',
            'data' => [
                'balance' => '0.00',
                'current_plan' => [
                    'name' => '无套餐',
                    'is_unlimited' => 0,
                    'remain_quota' => 1000,
                    'expire_at' => null
                ],
                'today_usage' => 0,
                'total_usage' => 0,
                'announcements' => [],
                'user_id' => 2
            ],
            'request_id' => ''
        ]);
    }
}
