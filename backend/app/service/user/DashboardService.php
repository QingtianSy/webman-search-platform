<?php

namespace app\service\user;

use app\repository\mysql\AnnouncementRepository;
use PDO;
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
        if (config('integration.log_source', 'mock') === 'real') {
            return $this->getTodayUsageReal($userId);
        }
        return $this->getTodayUsageMock($userId);
    }

    protected function getTodayUsageMock(int $userId): int
    {
        $file = base_path() . '/storage/logs/search_logs.jsonl';
        if (!is_file($file)) {
            return 0;
        }
        $today = date('Y-m-d');
        $count = 0;
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines ?: [] as $line) {
            $row = json_decode($line, true);
            if (is_array($row) && (int) ($row['user_id'] ?? 0) === $userId && str_starts_with((string) ($row['created_at'] ?? ''), $today)) {
                $count++;
            }
        }
        return $count;
    }

    protected function getTodayUsageReal(int $userId): int
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
            error_log("[DashboardService] getTodayUsageReal failed: " . $e->getMessage());
            return 0;
        }
    }
}
