<?php

namespace app\service\payment;

use support\adapter\EpayClient;

class PaymentService
{
    public function createPayUrl(array $order): string
    {
        $epay = new EpayClient();
        $name = $order['type'] == 2 ? '套餐购买' : '余额充值';
        $params = [
            'type' => $order['pay_type'],
            'out_trade_no' => $order['order_no'],
            'notify_url' => $this->getNotifyUrl(),
            'return_url' => $this->getReturnUrl(),
            'name' => $name,
            'money' => $order['amount'],
        ];
        return $epay->getPayLink($params);
    }

    private function getNotifyUrl(): string
    {
        $host = rtrim(getenv('APP_URL') ?: 'http://127.0.0.1:8787', '/');
        return $host . '/callback/epay/notify';
    }

    private function getReturnUrl(): string
    {
        $host = rtrim(getenv('APP_URL') ?: 'http://127.0.0.1:8787', '/');
        return $host . '/callback/epay/return';
    }
}
