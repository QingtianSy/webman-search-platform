<?php

namespace app\service\user;

use app\exception\BusinessException;
use app\repository\mysql\AnnouncementRepository;
use support\adapter\MySqlClient;

class DashboardService
{
    public function overview(int $userId): array
    {
        $billing = new BillingService();
        $wallet = $billing->wallet($userId);
        $subscription = $billing->currentPlan($userId);
        // 公告走 Strict：之前 DB 故障返 [] → Dashboard 显示"无公告"，掩盖公告表不可达。
        try {
            $announcements = (new AnnouncementRepository())->latestStrict();
        } catch (\RuntimeException $e) {
            throw new BusinessException('Dashboard 数据源暂不可用，请稍后重试', 50001);
        }

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
        // 之前 MySQL 掉线时这里归零，用户会以为"今天没搜过题"，直接影响额度反馈。
        // 改为把数据源不可用暴露为 50001，由前端统一提示"数据加载失败"。
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new BusinessException('Dashboard 数据源暂不可用，请稍后重试', 50001);
        }
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM search_logs WHERE user_id = :user_id AND created_at >= CURDATE()');
            $stmt->execute(['user_id' => $userId]);
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log("[DashboardService] getTodayUsage failed: " . $e->getMessage());
            throw new BusinessException('Dashboard 数据源暂不可用，请稍后重试', 50001);
        }
    }
}
