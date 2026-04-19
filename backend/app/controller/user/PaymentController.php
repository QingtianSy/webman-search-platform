<?php

namespace app\controller\user;

use app\repository\mysql\OperateLogRepository;
use app\service\payment\OrderService;
use app\service\payment\PaymentService;
use support\ApiResponse;
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

        if (!in_array($payType, ['alipay', 'wxpay', 'qqpay', 'bank'])) {
            return ApiResponse::error(40001, '不支持的支付方式');
        }

        if ($type === 2) {
            $planId = (int) $request->input('plan_id', 0);
            if ($planId <= 0) {
                return ApiResponse::error(40001, '请选择套餐');
            }
            $pdo = MySqlClient::pdo();
            if (!$pdo) {
                return ApiResponse::error(500, '服务异常');
            }
            $stmt = $pdo->prepare('SELECT id, price, status FROM plans WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $planId]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$plan || (int) $plan['status'] !== 1) {
                return ApiResponse::error(40001, '套餐不存在或已下架');
            }
            $amount = $plan['price'];
        } else {
            $amount = $request->input('amount', '0');
            if (bccomp($amount, '0.01', 2) < 0) {
                return ApiResponse::error(40001, '充值金额不能小于0.01');
            }
        }

        $orderService = new OrderService();
        $order = $orderService->create($userId, $type, $amount, $payType, $planId);
        if (empty($order)) {
            return ApiResponse::error(500, '创建订单失败');
        }

        $payUrl = (new PaymentService())->createPayUrl($order);
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
}
