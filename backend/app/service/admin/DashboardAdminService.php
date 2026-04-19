<?php

namespace app\service\admin;

use PDO;
use support\adapter\MySqlClient;

class DashboardAdminService
{
    public function overview(): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return $this->emptyStats();
        }

        try {
            return [
                'total_users' => $this->countAll($pdo, 'users'),
                'today_users' => $this->countToday($pdo, 'users'),
                'total_searches' => $this->countAll($pdo, 'search_logs'),
                'today_searches' => $this->countToday($pdo, 'search_logs'),
                'total_order_amount' => $this->sumAmount($pdo, null),
                'today_order_amount' => $this->sumAmount($pdo, 'CURDATE()'),
                'total_questions' => $this->countAll($pdo, 'questions'),
            ];
        } catch (\PDOException $e) {
            error_log("[DashboardAdminService] overview failed: " . $e->getMessage());
            return $this->emptyStats();
        }
    }

    protected function countAll(PDO $pdo, string $table): int
    {
        return (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    }

    protected function countToday(PDO $pdo, string $table): int
    {
        return (int) $pdo->query("SELECT COUNT(*) FROM `{$table}` WHERE created_at >= CURDATE()")->fetchColumn();
    }

    protected function sumAmount(PDO $pdo, ?string $dateExpr): string
    {
        $where = 'WHERE status = 1';
        if ($dateExpr !== null) {
            $where .= " AND paid_at >= {$dateExpr}";
        }
        $result = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM `order` {$where}")->fetchColumn();
        return number_format((float) $result, 2, '.', '');
    }

    protected function emptyStats(): array
    {
        return [
            'total_users' => 0,
            'today_users' => 0,
            'total_searches' => 0,
            'today_searches' => 0,
            'total_order_amount' => '0.00',
            'today_order_amount' => '0.00',
            'total_questions' => 0,
        ];
    }
}
