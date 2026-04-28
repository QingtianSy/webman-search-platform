<?php

namespace app\service\payment;

use support\adapter\EpayClient;

class PaymentService
{
    public function createPayUrl(array $order, ?string $requestHost = null): string
    {
        $epay = new EpayClient();
        $name = $order['type'] == 2 ? '套餐购买' : '余额充值';
        $params = [
            'type' => $order['pay_type'],
            'out_trade_no' => $order['order_no'],
            'notify_url' => $this->getNotifyUrl(),
            'return_url' => $this->getReturnUrl($requestHost),
            'name' => $name,
            'money' => number_format((float) $order['amount'], 2, '.', ''),
        ];
        return $epay->getPayLink($params);
    }

    private function getNotifyUrl(): string
    {
        $host = rtrim(getenv('APP_URL') ?: '', '/');
        if ($host === '') {
            throw new \RuntimeException('APP_URL 环境变量未配置，无法生成支付回调地址');
        }
        return $host . '/callback/epay/notify';
    }

    private function getReturnUrl(?string $requestHost = null): string
    {
        if ($requestHost !== null && $requestHost !== '') {
            return rtrim($requestHost, '/') . '/callback/epay/return';
        }
        $host = rtrim(getenv('APP_URL') ?: '', '/');
        if ($host === '') {
            throw new \RuntimeException('APP_URL 环境变量未配置，无法生成支付回跳地址');
        }
        return $host . '/callback/epay/return';
    }
}
