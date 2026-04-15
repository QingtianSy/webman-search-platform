<?php

namespace app\service\user;

use app\repository\mysql\AnnouncementRepository;
use app\repository\mysql\SubscriptionRepository;
use app\repository\mysql\WalletRepository;

class DashboardService
{
    public function overview(int $userId): array
    {
        $wallet = (new WalletRepository())->findByUserId($userId);
        $subscription = (new SubscriptionRepository())->findCurrentByUserId($userId);
        $announcements = (new AnnouncementRepository())->latest();

        return [
            'balance' => $wallet['balance'] ?? '0.00',
            'current_plan' => [
                'name' => $subscription['name'] ?? '无套餐',
                'is_unlimited' => $subscription['is_unlimited'] ?? 0,
                'remain_quota' => $subscription['remain_quota'] ?? 0,
                'expire_at' => $subscription['expire_at'] ?? null,
            ],
            'today_usage' => 0,
            'total_usage' => $subscription['used_quota'] ?? 0,
            'announcements' => $announcements,
            'user_id' => $userId,
        ];
    }
}
