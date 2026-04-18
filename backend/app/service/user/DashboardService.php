<?php

namespace app\service\user;

use app\repository\mysql\AnnouncementRepository;
use support\adapter\MySqlClient;

class DashboardService
{
    public function overview(int $userId): array
    {
        $billing = new BillingService();
        $wallet = $billing->wallet($userId);
        $subscription = $billing->currentPlan($userId);
        $announcements = (new AnnouncementRepository())->latest();

        return [
            'balance' => $wallet['balance'] ?? '0.00',
            'current_plan' => [
                'name' => $subscription['name'] ?? '无套餐',
                'is_unlimited' => $subscription['is_unlimited'] ?? 0,
                'remain_quota' => $subscription['remain_quota'] ?? 0,
                'expire_at' => $subscription['expire_at'] ?? null,
            ],
            'today_usage' => $this->getTodayUsage($userId),
            'total_usage' => $subscription['used_quota'] ?? 0,
            'announcements' => $announcements,
            'user_id' => $userId,
        ];
    }

    protected function getTodayUsage(int $userId): int
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return 0;
        }
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM search_logs WHERE user_id = :user_id AND created_at >= CURDATE()');
            $stmt->execute(['user_id' => $userId]);
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log("[DashboardService] getTodayUsage failed: " . $e->getMessage());
            return 0;
        }
    }
}
