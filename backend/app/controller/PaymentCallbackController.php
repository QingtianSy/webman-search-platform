<?php

namespace app\controller;

use app\repository\mysql\OrderRepository;
use app\service\payment\CallbackService;
use support\Request;
use support\Response;

class PaymentCallbackController
{
    public function notify(Request $request)
    {
        try {
            $params = $request->all();
            $service = new CallbackService();
            $ok = $service->handleNotify($params);
            return new Response(200, [], $ok ? 'success' : 'fail');
        } catch (\Throwable $e) {
            error_log("[PaymentCallbackController] notify exception: " . $e->getMessage());
            return new Response(200, [], 'fail');
        }
    }

    public function returnUrl(Request $request)
    {
        $orderNo = $request->get('out_trade_no', '');
        $message = '支付未完成';
        if ($orderNo !== '') {
            $order = (new OrderRepository())->findByOrderNo($orderNo);
            if (!empty($order) && (int) $order['status'] === 1) {
                $message = '支付成功';
            }
        }
        return new Response(200, ['Content-Type' => 'text/html'], '<script>alert("' . $message . '");window.close();</script>');
    }
}
