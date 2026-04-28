<?php

namespace app\service\payment;

use app\exception\BusinessException;
use app\repository\mysql\OrderRepository;

class OrderService
{
    public static function generateOrderNo(): string
    {
        return 'P' . date('YmdHis') . bin2hex(random_bytes(6));
    }

    public function create(int $userId, int $type, string $amount, string $payType, ?int $planId = null, ?array $planSnapshot = null): array
    {
        $repo = new OrderRepository();
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $orderNo = self::generateOrderNo();
            $id = $repo->create([
                'order_no' => $orderNo,
                'user_id' => $userId,
                'type' => $type,
                'plan_id' => $planId,
                'plan_name' => $planSnapshot['name'] ?? null,
                'plan_duration' => $planSnapshot['duration'] ?? null,
                'plan_quota' => $planSnapshot['quota'] ?? null,
                'plan_is_unlimited' => $planSnapshot['is_unlimited'] ?? null,
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
                    'plan_name' => $planSnapshot['name'] ?? null,
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
            'id' => (int) ($query['order_id'] ?? $query['id'] ?? 0),
            'order_no' => trim((string) ($query['order_no'] ?? '')),
            'trade_no' => trim((string) ($query['trade_no'] ?? '')),
            'amount' => $query['amount'] ?? '',
            'status' => $query['status'] ?? null,
            'pay_type' => trim((string) ($query['pay_type'] ?? $query['pay_method'] ?? '')),
            'date_from' => trim((string) ($query['date_from'] ?? '')),
            'date_to' => trim((string) ($query['date_to'] ?? '')),
        ];

        if ($filters['pay_type'] === 'wechat' || $filters['pay_type'] === 'wx') {
            $filters['pay_type'] = 'wxpay';
        } elseif ($filters['pay_type'] === 'qq') {
            $filters['pay_type'] = 'qqpay';
        }

        try {
            $result = (new OrderRepository())->listByUserId($userId, $page, $pageSize, $filters);
            $result['list'] = array_map(fn(array $row) => $this->normalizeRow($row), $result['list'] ?? []);
            return $result;
        } catch (\Throwable $e) {
            error_log('[OrderService] listByUserId failed: ' . $e->getMessage());
            throw new BusinessException('订单列表暂不可用，请稍后重试', 50001);
        }
    }

    protected function normalizeRow(array $row): array
    {
        $status = (int) ($row['status'] ?? 0);
        $type = (int) ($row['type'] ?? 1);
        $statusText = match ($status) {
            1 => 'success',
            2 => 'cancelled',
            default => 'pending',
        };

        return [
            'id' => isset($row['id']) ? (int) $row['id'] : null,
            'order_id' => isset($row['id']) ? (int) $row['id'] : null,
            'order_no' => (string) ($row['order_no'] ?? ''),
            'out_trade_no' => (string) ($row['order_no'] ?? ''),
            'trade_no' => $row['trade_no'] ?? null,
            'type' => $type,
            'order_type' => $type === 2 ? 'plan' : 'recharge',
            'plan_id' => isset($row['plan_id']) ? (int) $row['plan_id'] : null,
            'plan_name' => $row['plan_name'] ?? null,
            'amount' => (string) ($row['amount'] ?? '0'),
            'pay_type' => (string) ($row['pay_type'] ?? ''),
            'pay_method' => (string) ($row['pay_type'] ?? ''),
            'status' => $status,
            'status_text' => $statusText,
            'remark' => $type === 2
                ? ($row['plan_name'] ?? '套餐购买')
                : '余额充值',
            'fail_reason' => $status === 2 ? '订单已关闭或已取消' : null,
            'created_at' => $row['created_at'] ?? null,
            'paid_at' => $row['paid_at'] ?? null,
        ];
    }
}
