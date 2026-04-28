<?php

namespace app\controller\user;

use app\exception\BusinessException;
use app\repository\mysql\OperateLogRepository;
use app\repository\mysql\SystemConfigRepository;
use app\service\payment\OrderService;
use app\service\payment\PaymentService;
use support\adapter\EpayClient;
use support\adapter\MySqlClient;
use support\ApiResponse;
use support\Db;
use support\Request;
use PDO;

class PaymentController
{
    public function create(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $type = $this->parseOrderType($request);
        $payType = $this->normalizePayType((string) $request->input('pay_type', $request->input('pay_method', 'alipay')));
        $planId = null;
        $amount = '0';

        if (!in_array($type, [1, 2], true)) {
            return ApiResponse::error(40001, '不支持的订单类型');
        }

        if (!in_array($payType, ['alipay', 'wxpay', 'qqpay', 'bank'], true)) {
            return ApiResponse::error(40001, '不支持的支付方式');
        }

        $planSnapshot = null;
        if ($type === 2) {
            $planId = (int) $request->input('plan_id', 0);
            if ($planId <= 0) {
                return ApiResponse::error(40001, '请选择套餐');
            }
            $pdo = MySqlClient::pdo();
            if (!$pdo) {
                return ApiResponse::error(50001, '订单服务暂不可用，请稍后重试');
            }
            $stmt = $pdo->prepare('SELECT id, name, price, duration, quota, is_unlimited, status FROM plans WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $planId]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$plan || (int) $plan['status'] !== 1) {
                return ApiResponse::error(40001, '套餐不存在或已下架');
            }
            $amount = (string) $plan['price'];
            $planSnapshot = [
                'name' => $plan['name'],
                'duration' => (int) $plan['duration'],
                'quota' => (int) $plan['quota'],
                'is_unlimited' => (int) $plan['is_unlimited'],
            ];
        } else {
            $amount = (string) $request->input('amount', '0');
            try {
                $paymentConfigs = (new SystemConfigRepository())->getByGroupStrict('payment');
            } catch (\Throwable $e) {
                error_log('[PaymentController] read payment config failed: ' . $e->getMessage());
                return ApiResponse::error(50001, '支付配置暂不可用，请稍后重试');
            }
            $cfgMap = array_column($paymentConfigs, 'config_value', 'config_key');
            if (!isset($cfgMap['payment_min_amount'], $cfgMap['payment_max_amount'])) {
                error_log('[PaymentController] payment_min_amount/payment_max_amount missing');
                return ApiResponse::error(50001, '支付配置未初始化，请联系管理员');
            }
            $minAmount = (string) $cfgMap['payment_min_amount'];
            $maxAmount = (string) $cfgMap['payment_max_amount'];
            if (!preg_match('/^\d+(\.\d{1,2})?$/', $amount) || bccomp($amount, $minAmount, 2) < 0 || bccomp($amount, $maxAmount, 2) > 0) {
                return ApiResponse::error(40001, "充值金额需在 {$minAmount} ~ {$maxAmount} 之间");
            }
        }

        $appUrl = rtrim((string) getenv('APP_URL'), '/');
        if ($appUrl === '') {
            return ApiResponse::error(50001, '支付服务未配置，请联系管理员');
        }

        if (!(new EpayClient())->isConfigured()) {
            return ApiResponse::error(50001, '支付网关未配置，请联系管理员');
        }

        $order = (new OrderService())->create($userId, $type, $amount, $payType, $planId, $planSnapshot);
        if (empty($order)) {
            return ApiResponse::error(50001, '创建订单失败，请稍后重试');
        }

        try {
            $payUrl = (new PaymentService())->createPayUrl($order);
        } catch (\Throwable $e) {
            error_log("[PaymentController] createPayUrl failed, cancelling order {$order['order_no']}: " . $e->getMessage());
            $pdo = MySqlClient::pdo();
            if ($pdo) {
                $stmt = $pdo->prepare('UPDATE `order` SET status = 2 WHERE order_no = :no AND status = 0');
                $stmt->execute(['no' => $order['order_no']]);
            }
            return ApiResponse::error(50001, '支付链路异常，请稍后重试');
        }

        (new OperateLogRepository())->create([
            'user_id' => $userId,
            'module' => 'payment',
            'action' => 'create',
            'content' => "创建订单: {$order['order_no']}, 金额: {$amount}",
            'ip' => $request->getRealIp(),
        ]);

        return ApiResponse::success($this->serializeCreatedOrder($order, $payUrl));
    }

    public function list(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        return ApiResponse::success((new OrderService())->listByUserId($userId, $request->all()));
    }

    public function methods(Request $request)
    {
        try {
            $rows = (new SystemConfigRepository())->getByGroupStrict('payment');
        } catch (\RuntimeException $e) {
            throw new BusinessException('支付配置暂不可用，请稍后重试', 50001);
        }

        $kv = array_column($rows, 'config_value', 'config_key');
        $epayReady = !empty($kv['epay_apiurl']) && !empty($kv['epay_pid']) && !empty($kv['epay_key']);
        return ApiResponse::success($this->buildPayMethodList($epayReady));
    }

    public function detail(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $row = $this->findOwnedOrder($request, $userId);
        if (!$row) {
            return ApiResponse::error(40004, '订单不存在');
        }

        $data = $this->serializeOrder($row);
        if ((int) ($row['status'] ?? 0) === 0) {
            try {
                $payUrl = (new PaymentService())->createPayUrl($row);
                $data['pay_url'] = $payUrl;
                $data['qr_code_url'] = $payUrl;
            } catch (\Throwable $e) {
                error_log('[PaymentController] detail createPayUrl soft-fail: ' . $e->getMessage());
            }
        }

        return ApiResponse::success($data);
    }

    public function continuePay(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $row = $this->findOwnedOrder($request, $userId);
        if (!$row) {
            return ApiResponse::error(40004, '订单不存在');
        }
        if ((int) $row['status'] !== 0) {
            return ApiResponse::error(40001, '订单已完成或已关闭，无法继续支付');
        }

        try {
            $payUrl = (new PaymentService())->createPayUrl($row);
        } catch (\Throwable $e) {
            error_log('[PaymentController] continuePay createPayUrl failed: ' . $e->getMessage());
            throw new BusinessException('支付链路异常，请稍后重试', 50001);
        }

        (new OperateLogRepository())->create([
            'user_id' => $userId,
            'module' => 'payment',
            'action' => 'continue',
            'content' => "继续支付 {$row['order_no']}",
            'ip' => $request->getRealIp(),
        ]);

        return ApiResponse::success($this->serializeCreatedOrder($row, $payUrl));
    }

    public function cancel(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $row = $this->findOwnedOrder($request, $userId);
        if (!$row) {
            return ApiResponse::error(40004, '订单不存在');
        }

        try {
            $affected = Db::table('order')
                ->where('id', (int) $row['id'])
                ->where('user_id', $userId)
                ->where('status', 0)
                ->update(['status' => 2]);
        } catch (\Throwable $e) {
            error_log('[PaymentController] cancel failed: ' . $e->getMessage());
            throw new BusinessException('订单服务暂不可用，请稍后重试', 50001);
        }

        if ($affected === 0) {
            return ApiResponse::error(40001, '订单已完成或已关闭，无法取消');
        }

        (new OperateLogRepository())->create([
            'user_id' => $userId,
            'module' => 'payment',
            'action' => 'cancel',
            'content' => "取消订单 {$row['order_no']}",
            'ip' => $request->getRealIp(),
        ]);

        return ApiResponse::success([
            'order_id' => (int) $row['id'],
            'order_no' => $row['order_no'],
            'status' => 2,
            'status_text' => 'cancelled',
        ], '订单已取消');
    }

    protected function parseOrderType(Request $request): int
    {
        $orderType = strtolower(trim((string) $request->input('order_type', '')));
        if ($orderType === 'recharge') {
            return 1;
        }
        if ($orderType === 'plan') {
            return 2;
        }
        if ($orderType !== '') {
            return 0;
        }
        return (int) $request->input('type', 1);
    }

    protected function normalizePayType(string $payType): string
    {
        $payType = strtolower(trim($payType));
        $aliases = [
            'qq' => 'qqpay',
            'wechat' => 'wxpay',
            'wx' => 'wxpay',
        ];
        return $aliases[$payType] ?? $payType;
    }

    protected function buildPayMethodList(bool $enabled): array
    {
        $channels = [
            ['code' => 'alipay', 'name' => '支付宝', 'icon' => 'alipay'],
            ['code' => 'wxpay', 'name' => '微信支付', 'icon' => 'wechat'],
            ['code' => 'qqpay', 'name' => 'QQ 钱包', 'icon' => 'qq'],
            ['code' => 'bank', 'name' => '网银', 'icon' => 'bank'],
        ];

        return array_map(function (array $channel) use ($enabled) {
            $channel['enabled'] = $enabled ? 1 : 0;
            return $channel;
        }, $channels);
    }

    protected function findOwnedOrder(Request $request, int $userId): ?array
    {
        $orderNo = trim((string) $request->input('order_no', $request->input('out_trade_no', '')));
        if ($orderNo !== '') {
            $row = Db::table('order')->where('order_no', $orderNo)->where('user_id', $userId)->first();
            return $row ? (array) $row : null;
        }

        $orderId = (int) $request->input('order_id', 0);
        if ($orderId > 0) {
            $row = Db::table('order')->where('id', $orderId)->where('user_id', $userId)->first();
            return $row ? (array) $row : null;
        }

        return null;
    }

    protected function serializeCreatedOrder(array $order, string $payUrl): array
    {
        $data = $this->serializeOrder($order);
        $data['pay_url'] = $payUrl;
        $data['qr_code_url'] = $payUrl;
        return $data;
    }

    protected function serializeOrder(array $row): array
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
            'pay_url' => null,
            'qr_code_url' => null,
        ];
    }
}
