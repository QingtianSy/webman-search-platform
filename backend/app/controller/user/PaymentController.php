<?php

namespace app\controller\user;

use app\exception\BusinessException;
use app\repository\mysql\OperateLogRepository;
use app\repository\mysql\SystemConfigRepository;
use app\service\payment\OrderService;
use app\service\payment\PaymentService;
use support\adapter\EpayClient;
use support\ApiResponse;
use support\Db;
use support\Request;
use PDO;
use support\adapter\MySqlClient;

class PaymentController
{
    public function create(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $type = (int) $request->input('type', 1);
        $payType = $request->input('pay_type', 'alipay');
        $planId = null;
        $amount = '0';

        if (!in_array($type, [1, 2], true)) {
            return ApiResponse::error(40001, '不支持的订单类型');
        }

        if (!in_array($payType, ['alipay', 'wxpay', 'qqpay', 'bank'])) {
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
                return ApiResponse::error(500, '服务异常');
            }
            $stmt = $pdo->prepare('SELECT id, name, price, duration, quota, is_unlimited, status FROM plans WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $planId]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$plan || (int) $plan['status'] !== 1) {
                return ApiResponse::error(40001, '套餐不存在或已下架');
            }
            $amount = $plan['price'];
            $planSnapshot = [
                'name' => $plan['name'],
                'duration' => (int) $plan['duration'],
                'quota' => (int) $plan['quota'],
                'is_unlimited' => (int) $plan['is_unlimited'],
            ];
        } else {
            $amount = $request->input('amount', '0');
            try {
                $paymentConfigs = (new SystemConfigRepository())->getByGroupStrict('payment');
            } catch (\Throwable $e) {
                error_log("[PaymentController] read payment config failed: " . $e->getMessage());
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
                return ApiResponse::error(40001, "充值金额须在 {$minAmount} ~ {$maxAmount} 之间");
            }
        }

        $appUrl = rtrim(getenv('APP_URL') ?: '', '/');
        if ($appUrl === '') {
            return ApiResponse::error(500, '支付服务未配置，请联系管理员');
        }

        if (!(new EpayClient())->isConfigured()) {
            return ApiResponse::error(500, '支付网关未配置，请联系管理员');
        }

        $orderService = new OrderService();
        $order = $orderService->create($userId, $type, $amount, $payType, $planId, $planSnapshot);
        if (empty($order)) {
            return ApiResponse::error(500, '创建订单失败');
        }

        try {
            $payUrl = (new PaymentService())->createPayUrl($order);
        } catch (\Throwable $e) {
            error_log("[PaymentController] createPayUrl failed, cancelling order {$order['order_no']}: " . $e->getMessage());
            $pdo = MySqlClient::pdo();
            if ($pdo) {
                $stmt = $pdo->prepare("UPDATE `order` SET status = 2 WHERE order_no = :no AND status = 0");
                $stmt->execute(['no' => $order['order_no']]);
            }
            return ApiResponse::error(500, '支付链路异常，请联系管理员');
        }

        (new OperateLogRepository())->create(['user_id' => $userId, 'module' => 'payment', 'action' => 'create', 'content' => "创建订单: {$order['order_no']}, 金额: {$amount}", 'ip' => $request->getRealIp()]);
        return ApiResponse::success([
            'order_no' => $order['order_no'],
            'pay_url' => $payUrl,
        ]);
    }

    public function list(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $query = $request->all();
        $result = (new OrderService())->listByUserId($userId, $query);
        return ApiResponse::success($result);
    }

    // 支付方式：从 system_configs group=payment 推导，epay 相关 key 都配齐才算"已启用"。
    // 返回静态渠道列表 + enabled 状态，前端 recharge Step1 依此决定哪些按钮可点。
    public function methods(Request $request)
    {
        try {
            $rows = (new SystemConfigRepository())->getByGroupStrict('payment');
        } catch (\RuntimeException $e) {
            throw new BusinessException('支付配置暂不可用，请稍后重试', 50001);
        }
        $kv = array_column($rows, 'config_value', 'config_key');
        $epayReady = !empty($kv['epay_apiurl']) && !empty($kv['epay_pid']) && !empty($kv['epay_key']);

        $channels = [
            ['code' => 'alipay', 'name' => '支付宝', 'icon' => 'alipay'],
            ['code' => 'wxpay', 'name' => '微信支付', 'icon' => 'wechat'],
            ['code' => 'qqpay', 'name' => 'QQ 钱包', 'icon' => 'qq'],
            ['code' => 'bank', 'name' => '网银', 'icon' => 'bank'],
        ];
        $result = [];
        foreach ($channels as $ch) {
            $ch['enabled'] = $epayReady ? 1 : 0;
            $result[] = $ch;
        }
        return ApiResponse::success($result);
    }

    // 订单详情：按 order_no 查单并校验归属；pending 订单返回 pay_url 便于继续支付。
    public function detail(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $orderNo = trim((string) $request->get('order_no', ''));
        if ($orderNo === '') {
            return ApiResponse::error(40001, '订单号不能为空');
        }
        $row = Db::table('order')->where('order_no', $orderNo)->where('user_id', $userId)->first();
        if (!$row) {
            return ApiResponse::error(40004, '订单不存在');
        }
        $row = (array) $row;
        $payUrl = null;
        if ((int) $row['status'] === 0) {
            try {
                $payUrl = (new PaymentService())->createPayUrl($row);
            } catch (\Throwable $e) {
                // 不阻断详情返回；前端可以点"继续支付"触发重建
                error_log('[PaymentController] detail createPayUrl soft-fail: ' . $e->getMessage());
            }
        }
        return ApiResponse::success([
            'order_no' => $row['order_no'],
            'trade_no' => $row['trade_no'] ?? null,
            'type' => (int) $row['type'],
            'plan_id' => $row['plan_id'] ?? null,
            'amount' => (string) $row['amount'],
            'pay_type' => $row['pay_type'],
            'status' => (int) $row['status'],
            'created_at' => $row['created_at'],
            'paid_at' => $row['paid_at'] ?? null,
            'pay_url' => $payUrl,
            'qr_code_url' => $payUrl, // 前端有 QR 用的直接用同一 URL 生成二维码
        ]);
    }

    // 继续支付：仅 status=0 允许，重新生成支付链接。过期/已支付 → 40001。
    public function continuePay(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $orderNo = trim((string) $request->post('order_no', ''));
        if ($orderNo === '') {
            return ApiResponse::error(40001, '订单号不能为空');
        }
        $row = Db::table('order')->where('order_no', $orderNo)->where('user_id', $userId)->first();
        if (!$row) {
            return ApiResponse::error(40004, '订单不存在');
        }
        $row = (array) $row;
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
            'user_id' => $userId, 'module' => 'payment', 'action' => 'continue',
            'content' => "继续支付 {$orderNo}", 'ip' => $request->getRealIp(),
        ]);
        return ApiResponse::success([
            'order_no' => $row['order_no'],
            'pay_url' => $payUrl,
            'qr_code_url' => $payUrl,
        ]);
    }

    // 取消订单：仅 status=0 可取消，置为 2（已过期/已取消）；WHERE 条件带 status=0 天然并发安全。
    public function cancel(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $orderNo = trim((string) $request->post('order_no', ''));
        if ($orderNo === '') {
            return ApiResponse::error(40001, '订单号不能为空');
        }
        try {
            $affected = Db::table('order')
                ->where('order_no', $orderNo)
                ->where('user_id', $userId)
                ->where('status', 0)
                ->update(['status' => 2]);
        } catch (\Throwable $e) {
            error_log('[PaymentController] cancel failed: ' . $e->getMessage());
            throw new BusinessException('订单服务暂不可用，请稍后重试', 50001);
        }
        if ($affected === 0) {
            // 可能：订单不存在 / 不归属 / 已完成
            $exists = Db::table('order')->where('order_no', $orderNo)->where('user_id', $userId)->exists();
            if (!$exists) {
                return ApiResponse::error(40004, '订单不存在');
            }
            return ApiResponse::error(40001, '订单已完成或已关闭，无法取消');
        }
        (new OperateLogRepository())->create([
            'user_id' => $userId, 'module' => 'payment', 'action' => 'cancel',
            'content' => "取消订单 {$orderNo}", 'ip' => $request->getRealIp(),
        ]);
        return ApiResponse::success(null, '订单已取消');
    }
}
