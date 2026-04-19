<?php

namespace app\service\payment;

use app\repository\mysql\OrderRepository;

class OrderService
{
    public static function generateOrderNo(): string
    {
        return 'P' . date('YmdHis') . bin2hex(random_bytes(6));
    }

    public function create(int $userId, int $type, string $amount, string $payType, ?int $planId = null): array
    {
        $repo = new OrderRepository();
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $orderNo = self::generateOrderNo();
            $id = $repo->create([
                'order_no' => $orderNo,
                'user_id' => $userId,
                'type' => $type,
                'plan_id' => $planId,
                'amount' => $amount,
                'pay_type' => $payType,
            ]);
            if ($id > 0) {
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
        }
        return [];
    }

    public function listByUserId(int $userId, array $query = []): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($query['page_size'] ?? 20)));
        $filters = [
            'order_no' => $query['order_no'] ?? '',
            'trade_no' => $query['trade_no'] ?? '',
            'amount' => $query['amount'] ?? '',
        ];
        return (new OrderRepository())->listByUserId($userId, $page, $pageSize, $filters);
    }
}
