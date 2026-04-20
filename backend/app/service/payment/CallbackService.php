<?php

namespace app\service\payment;

use app\repository\mysql\OrderRepository;
use app\repository\mysql\PaymentLogRepository;
use app\repository\redis\QuotaCacheRepository;
use support\adapter\EpayClient;
use support\adapter\MySqlClient;

class CallbackService
{
    public function handleNotify(array $params): bool
    {
        $epay = new EpayClient();
        if (!$epay->verify($params)) {
            error_log("[CallbackService] verify failed: " . json_encode($params));
            return false;
        }

        $tradeStatus = $params['trade_status'] ?? '';
        if ($tradeStatus !== 'TRADE_SUCCESS') {
            return false;
        }

        $orderNo = $params['out_trade_no'] ?? '';
        $tradeNo = $params['trade_no'] ?? '';
        $money = $params['money'] ?? '0';

        $repo = new OrderRepository();
        $order = $repo->findByOrderNo($orderNo);
        if (empty($order)) {
            error_log("[CallbackService] order not found: $orderNo");
            return false;
        }

        if ((int) $order['status'] === 1) {
            return true;
        }

        if (bccomp($order['amount'], $money, 2) !== 0) {
            error_log("[CallbackService] amount mismatch: order={$order['amount']}, callback=$money");
            return false;
        }

        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            error_log("[CallbackService] PDO unavailable for order={$orderNo}");
            return false;
        }

        try {
            $pdo->beginTransaction();

            if (!$repo->markPaid($orderNo, $tradeNo)) {
                $pdo->rollBack();
                error_log("[CallbackService] markPaid failed: $orderNo");
                return false;
            }

            $type = (int) $order['type'];
            $fulfilled = false;
            if ($type === 1) {
                $fulfilled = (new WalletService())->recharge((int) $order['user_id'], $order['amount'], $orderNo);
            } elseif ($type === 2) {
                $planSnapshot = null;
                if (!empty($order['plan_name'])) {
                    $planSnapshot = [
                        'name' => $order['plan_name'],
                        'duration' => (int) ($order['plan_duration'] ?? 0),
                        'quota' => (int) ($order['plan_quota'] ?? 0),
                        'is_unlimited' => (int) ($order['plan_is_unlimited'] ?? 0),
                    ];
                }
                $fulfilled = (new SubscriptionService())->activate((int) $order['user_id'], (int) $order['plan_id'], $orderNo, $planSnapshot);
            }

            if (!$fulfilled) {
                $pdo->rollBack();
                error_log("[CallbackService] fulfillment failed for order={$orderNo}, transaction rolled back");
                return false;
            }

            $logOk = (new PaymentLogRepository())->create([
                'user_id' => $order['user_id'],
                'order_no' => $orderNo,
                'amount' => $order['amount'],
                'pay_method' => $order['pay_type'],
                'status' => 1,
                'remark' => $type === 1 ? '余额充值' : '套餐购买',
            ]);
            if (!$logOk) {
                $pdo->rollBack();
                error_log("[CallbackService] payment log write failed for order={$orderNo}");
                return false;
            }

            $pdo->commit();
            // 订阅激活链路的额度缓存失效必须在 CallbackService 自己的 commit 之后，
            // SubscriptionService::activate 在外层事务场景下不自己删缓存，由这里兜底。
            if ($type === 2) {
                (new QuotaCacheRepository())->deleteUserQuota((int) $order['user_id']);
            }
            return true;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("[CallbackService] transaction failed for order={$orderNo}: " . $e->getMessage());
            return false;
        }
    }
}
