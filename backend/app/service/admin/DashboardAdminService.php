<?php

namespace app\service\admin;

use support\adapter\MySqlClient;
use support\adapter\RedisClient;

class DashboardAdminService
{
    protected const CACHE_KEY = 'dashboard:overview';
    protected const CACHE_TTL = 60;

    public function overview(): array
    {
        $cached = $this->fromCache();
        if ($cached !== null) {
            return $cached;
        }

        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return $this->emptyStats();
        }

        try {
            $sql = <<<'SQL'
SELECT
  (SELECT COUNT(*) FROM users) AS total_users,
  (SELECT COUNT(*) FROM users WHERE created_at >= CURDATE()) AS today_users,
  (SELECT COUNT(*) FROM search_logs) AS total_searches,
  (SELECT COUNT(*) FROM search_logs WHERE created_at >= CURDATE()) AS today_searches,
  (SELECT COALESCE(SUM(amount),0) FROM `order` WHERE status=1) AS total_order_amount,
  (SELECT COALESCE(SUM(amount),0) FROM `order` WHERE status=1 AND paid_at >= CURDATE()) AS today_order_amount,
  (SELECT COUNT(*) FROM questions) AS total_questions
SQL;
            $row = $pdo->query($sql)->fetch(\PDO::FETCH_ASSOC);
            $stats = [
                'total_users' => (int) ($row['total_users'] ?? 0),
                'today_users' => (int) ($row['today_users'] ?? 0),
                'total_searches' => (int) ($row['total_searches'] ?? 0),
                'today_searches' => (int) ($row['today_searches'] ?? 0),
                'total_order_amount' => number_format((float) ($row['total_order_amount'] ?? 0), 2, '.', ''),
                'today_order_amount' => number_format((float) ($row['today_order_amount'] ?? 0), 2, '.', ''),
                'total_questions' => (int) ($row['total_questions'] ?? 0),
            ];
            $this->toCache($stats);
            return $stats;
        } catch (\PDOException $e) {
            error_log("[DashboardAdminService] overview failed: " . $e->getMessage());
            return $this->emptyStats();
        }
    }

    protected function fromCache(): ?array
    {
        $redis = RedisClient::connection();
        if (!$redis) {
            return null;
        }
        try {
            $val = $redis->get(RedisClient::key(self::CACHE_KEY));
            if ($val === false) {
                return null;
            }
            $data = json_decode($val, true);
            return is_array($data) ? $data : null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function toCache(array $stats): void
    {
        $redis = RedisClient::connection();
        if (!$redis) {
            return;
        }
        try {
            $redis->setex(RedisClient::key(self::CACHE_KEY), self::CACHE_TTL, json_encode($stats));
        } catch (\Throwable) {
        }
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
