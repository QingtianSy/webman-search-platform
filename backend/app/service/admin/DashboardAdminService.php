<?php

namespace app\service\admin;

use app\exception\BusinessException;
use app\repository\mongo\QuestionRepository;
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
            // 故障不再伪装成全零仪表盘 —— 以前 MySQL 掉线时管理端会看到"今日 0 用户 / 0 搜索"
            // 和"刚经历极其冷清的一天"没有区别，排障指引完全错误。
            throw new BusinessException('Dashboard 数据源暂不可用，请稍后重试', 50001);
        }

        try {
            $sql = <<<'SQL'
SELECT
  (SELECT COUNT(*) FROM users) AS total_users,
  (SELECT COUNT(*) FROM users WHERE created_at >= CURDATE()) AS today_users,
  (SELECT COUNT(*) FROM search_logs) AS total_searches,
  (SELECT COUNT(*) FROM search_logs WHERE created_at >= CURDATE()) AS today_searches,
  (SELECT COALESCE(SUM(amount),0) FROM `order` WHERE status=1) AS total_order_amount,
  (SELECT COALESCE(SUM(amount),0) FROM `order` WHERE status=1 AND paid_at >= CURDATE()) AS today_order_amount
SQL;
            $row = $pdo->query($sql)->fetch(\PDO::FETCH_ASSOC);
            // Mongo 题库计数同样走 strict：Mongo 挂掉时 total_questions=0 会让管理员
            // 误以为题库被清空，转而做 reindex / 导入等破坏性动作。
            $totalQuestions = (new QuestionRepository())->countByFiltersStrict([]);
            $stats = [
                'total_users' => (int) ($row['total_users'] ?? 0),
                'today_users' => (int) ($row['today_users'] ?? 0),
                'total_searches' => (int) ($row['total_searches'] ?? 0),
                'today_searches' => (int) ($row['today_searches'] ?? 0),
                'total_order_amount' => number_format((float) ($row['total_order_amount'] ?? 0), 2, '.', ''),
                'today_order_amount' => number_format((float) ($row['today_order_amount'] ?? 0), 2, '.', ''),
                'total_questions' => $totalQuestions,
            ];
            $this->toCache($stats);
            return $stats;
        } catch (BusinessException $e) {
            throw $e;
        } catch (\Throwable $e) {
            error_log("[DashboardAdminService] overview failed: " . $e->getMessage());
            throw new BusinessException('Dashboard 数据源暂不可用，请稍后重试', 50001);
        }
    }

    protected function fromCache(): ?array
    {
        $redis = RedisClient::connection();
        if (!$redis) {
            return null;
        }
        try {
            $val = $redis->get(RedisClient::key(self::CACHE_KEY, 'v1'));
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
            $redis->setex(RedisClient::key(self::CACHE_KEY, 'v1'), self::CACHE_TTL, json_encode($stats));
        } catch (\Throwable) {
        }
    }
}
