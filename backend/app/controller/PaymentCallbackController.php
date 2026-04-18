<?php

namespace app\controller;

use app\service\payment\CallbackService;
use support\Request;
use support\Response;

class PaymentCallbackController
{
    public function notify(Request $request)
    {
        $params = $request->get();
        $service = new CallbackService();
        $ok = $service->handleNotify($params);
        return new Response(200, [], $ok ? 'success' : 'fail');
    }

    public function returnUrl(Request $request)
    {
        $tradeStatus = $request->get('trade_status', '');
        if ($tradeStatus === 'TRADE_SUCCESS') {
            return new Response(200, ['Content-Type' => 'text/html'], '<script>alert("支付成功");window.close();</script>');
        }
        return new Response(200, ['Content-Type' => 'text/html'], '<script>alert("支付未完成");window.close();</script>');
    }
}
