<?php

namespace app\service\payment;

use app\repository\mysql\OrderRepository;

class OrderService
{
    public static function generateOrderNo(): string
    {
        return 'P' . date('YmdHis') . mt_rand(1000, 9999);
    }

    public function create(int $userId, int $type, string $amount, string $payType, ?int $planId = null): array
    {
        $orderNo = self::generateOrderNo();
        $repo = new OrderRepository();
        $id = $repo->create([
            'order_no' => $orderNo,
            'user_id' => $userId,
            'type' => $type,
            'plan_id' => $planId,
            'amount' => $amount,
            'pay_type' => $payType,
        ]);
        if ($id <= 0) {
            return [];
        }
        return [
            'id' => $id,
            'order_no' => $orderNo,
            'user_id' => $userId,
            'type' => $type,
            'plan_id' => $planId,
            'amount' => $amount,
            'pay_type' => $payType,
            'status' => 0,
        ];
    }

    public function listByUserId(int $userId, array $query = []): array
    {
        $page = (int) ($query['page'] ?? 1);
        $pageSize = (int) ($query['page_size'] ?? 20);
        $filters = [
            'order_no' => $query['order_no'] ?? '',
            'trade_no' => $query['trade_no'] ?? '',
            'amount' => $query['amount'] ?? '',
        ];
        return (new OrderRepository())->listByUserId($userId, $page, $pageSize, $filters);
    }
}
