<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class OrderRepository
{
    public function create(array $data): int
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return 0;
        }
        try {
            $stmt = $pdo->prepare('INSERT INTO `order` (order_no, user_id, type, plan_id, plan_name, plan_duration, plan_quota, plan_is_unlimited, amount, pay_type, status, created_at) VALUES (:order_no, :user_id, :type, :plan_id, :plan_name, :plan_duration, :plan_quota, :plan_is_unlimited, :amount, :pay_type, 0, NOW())');
            $stmt->execute([
                'order_no' => $data['order_no'],
                'user_id' => $data['user_id'],
                'type' => $data['type'],
                'plan_id' => $data['plan_id'] ?? null,
                'plan_name' => $data['plan_name'] ?? null,
                'plan_duration' => $data['plan_duration'] ?? null,
                'plan_quota' => $data['plan_quota'] ?? null,
                'plan_is_unlimited' => $data['plan_is_unlimited'] ?? null,
                'amount' => $data['amount'],
                'pay_type' => $data['pay_type'],
            ]);
            return (int) $pdo->lastInsertId();
        } catch (\PDOException $e) {
            error_log("[OrderRepository] create failed: " . $e->getMessage());
            return 0;
        }
    }

    public function findByOrderNo(string $orderNo): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('SELECT * FROM `order` WHERE order_no = :order_no LIMIT 1');
            $stmt->execute(['order_no' => $orderNo]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[OrderRepository] findByOrderNo failed: " . $e->getMessage());
            return [];
        }
    }

    public function markPaid(string $orderNo, string $tradeNo): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $stmt = $pdo->prepare('UPDATE `order` SET status = 1, trade_no = :trade_no, paid_at = NOW() WHERE order_no = :order_no AND status = 0');
            $stmt->execute([
                'order_no' => $orderNo,
                'trade_no' => $tradeNo,
            ]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("[OrderRepository] markPaid failed: " . $e->getMessage());
            return false;
        }
    }

    public function revertPaid(string $orderNo): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $stmt = $pdo->prepare('UPDATE `order` SET status = 0, trade_no = NULL, paid_at = NULL WHERE order_no = :order_no AND status = 1');
            $stmt->execute(['order_no' => $orderNo]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("[OrderRepository] revertPaid failed: " . $e->getMessage());
            return false;
        }
    }

    public function listByUserId(int $userId, int $page = 1, int $pageSize = 20, array $filters = []): array
    {
        // 订单历史空列表 = 用户"没下过单"视觉相同，但对于充过值的用户会造成严重误解。
        // 故障暴露为 RuntimeException，由 service 层转成 50001。
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $where = 'WHERE user_id = :user_id';
            $bind = ['user_id' => $userId];

            if (!empty($filters['order_no'])) {
                $where .= ' AND order_no LIKE :order_no';
                $bind['order_no'] = '%' . $filters['order_no'] . '%';
            }
            if (!empty($filters['trade_no'])) {
                $where .= ' AND trade_no LIKE :trade_no';
                $bind['trade_no'] = '%' . $filters['trade_no'] . '%';
            }
            if (isset($filters['amount']) && $filters['amount'] !== '') {
                $where .= ' AND amount = :amount';
                $bind['amount'] = $filters['amount'];
            }

            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM `order` $where");
            $countStmt->execute($bind);
            $total = (int) $countStmt->fetchColumn();

            $offset = ($page - 1) * $pageSize;
            $stmt = $pdo->prepare("SELECT order_no, trade_no, user_id, amount, pay_type, status, created_at, paid_at FROM `order` $where ORDER BY created_at DESC LIMIT :_limit OFFSET :_offset");
            foreach ($bind as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue('_limit', $pageSize, PDO::PARAM_INT);
            $stmt->bindValue('_offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return ['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize];
        } catch (\PDOException $e) {
            error_log("[OrderRepository] listByUserId failed: " . $e->getMessage());
            throw new \RuntimeException('order list query failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
