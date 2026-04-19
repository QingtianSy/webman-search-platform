<?php

namespace support\adapter;

use app\repository\mysql\SystemConfigRepository;

class EpayClient
{
    private string $apiurl;
    private string $pid;
    private string $signType;
    private string $key;
    private string $platformPublicKey;
    private string $merchantPrivateKey;

    private static ?array $configCache = null;

    public function __construct()
    {
        $cfg = self::loadConfig();
        $this->apiurl = rtrim($cfg['epay_apiurl'] ?? '', '/') . '/';
        $this->pid = $cfg['epay_pid'] ?? '';
        $this->signType = strtoupper($cfg['epay_sign_type'] ?? 'MD5');
        $this->key = $cfg['epay_key'] ?? '';
        $this->platformPublicKey = $cfg['epay_platform_public_key'] ?? '';
        $this->merchantPrivateKey = $cfg['epay_merchant_private_key'] ?? '';
    }

    private static function loadConfig(): array
    {
        if (self::$configCache !== null) {
            return self::$configCache;
        }
        $rows = (new SystemConfigRepository())->getByGroup('payment');
        $map = [];
        foreach ($rows as $row) {
            $map[$row['config_key']] = $row['config_value'] ?? '';
        }
        self::$configCache = $map;
        return $map;
    }

    public static function clearConfigCache(): void
    {
        self::$configCache = null;
    }

    public function pagePay(array $params): string
    {
        $requrl = $this->getSubmitUrl();
        $params = $this->buildRequestParam($params);

        $html = '<form id="dopay" action="' . $requrl . '" method="post">';
        foreach ($params as $k => $v) {
            $html .= '<input type="hidden" name="' . $k . '" value="' . htmlspecialchars((string)$v) . '"/>';
        }
        $html .= '<input type="submit" value="正在跳转"></form><script>document.getElementById("dopay").submit();</script>';
        return $html;
    }

    public function getPayLink(array $params): string
    {
        $requrl = $this->getSubmitUrl();
        $params = $this->buildRequestParam($params);
        return $requrl . '?' . http_build_query($params);
    }

    public function apiPay(array $params): array
    {
        return $this->execute($this->getApiPayPath(), $params);
    }

    public function verify(array $arr): bool
    {
        if (empty($arr) || empty($arr['sign'])) {
            return false;
        }

        if ($this->signType === 'RSA') {
            if (empty($arr['timestamp']) || abs(time() - (int)$arr['timestamp']) > 300) {
                return false;
            }
            return $this->rsaPublicVerify($this->getSignContent($arr), $arr['sign']);
        }

        $sign = $arr['sign'];
        return $sign === $this->md5Sign($this->getSignContent($arr));
    }

    public function queryOrder(string $tradeNo): array
    {
        if ($this->signType === 'RSA') {
            return $this->execute('api/pay/query', ['trade_no' => $tradeNo]);
        }
        $params = ['act' => 'order', 'pid' => $this->pid, 'trade_no' => $tradeNo];
        $params['sign'] = $this->md5Sign($this->getSignContent($params));
        $params['sign_type'] = 'MD5';
        $response = $this->httpRequest($this->apiurl . 'api.php?' . http_build_query($params));
        return json_decode($response, true) ?: [];
    }

    public function refund(string $outRefundNo, string $tradeNo, string $money): array
    {
        return $this->execute('api/pay/refund', [
            'trade_no' => $tradeNo,
            'money' => $money,
            'out_refund_no' => $outRefundNo,
        ]);
    }

    private function execute(string $path, array $params): array
    {
        $requrl = $this->apiurl . ltrim($path, '/');
        $params = $this->buildRequestParam($params);
        $response = $this->httpRequest($requrl, http_build_query($params));
        $arr = json_decode($response, true);
        if ($arr && ($arr['code'] ?? -1) == 0) {
            if ($this->signType === 'RSA' && !$this->verify($arr)) {
                throw new \RuntimeException('返回数据验签失败');
            }
            return $arr;
        }
        throw new \RuntimeException($arr['msg'] ?? '请求失败');
    }

    private function getSubmitUrl(): string
    {
        if ($this->signType === 'RSA') {
            return $this->apiurl . 'api/pay/submit';
        }
        return $this->apiurl . 'submit.php';
    }

    private function getApiPayPath(): string
    {
        if ($this->signType === 'RSA') {
            return 'api/pay/create';
        }
        return 'mapi.php';
    }

    private function buildRequestParam(array $params): array
    {
        $params['pid'] = $this->pid;

        if ($this->signType === 'RSA') {
            $params['timestamp'] = (string)time();
            $params['sign'] = $this->rsaPrivateSign($this->getSignContent($params));
            $params['sign_type'] = 'RSA';
        } else {
            $params['sign'] = $this->md5Sign($this->getSignContent($params));
            $params['sign_type'] = 'MD5';
        }

        return $params;
    }

    private function getSignContent(array $params): string
    {
        ksort($params);
        $parts = [];
        foreach ($params as $k => $v) {
            if (is_array($v) || $v === null || trim((string)$v) === '' || $k === 'sign' || $k === 'sign_type') {
                continue;
            }
            $parts[] = $k . '=' . $v;
        }
        return implode('&', $parts);
    }

    private function md5Sign(string $data): string
    {
        return md5($data . $this->key);
    }

    private function rsaPrivateSign(string $data): string
    {
        $key = "-----BEGIN PRIVATE KEY-----\n"
            . wordwrap($this->merchantPrivateKey, 64, "\n", true)
            . "\n-----END PRIVATE KEY-----";
        $privateKey = openssl_get_privatekey($key);
        if (!$privateKey) {
            throw new \RuntimeException('签名失败，商户私钥错误');
        }
        openssl_sign($data, $sign, $privateKey, OPENSSL_ALGO_SHA256);
        return base64_encode($sign);
    }

    private function rsaPublicVerify(string $data, string $sign): bool
    {
        $key = "-----BEGIN PUBLIC KEY-----\n"
            . wordwrap($this->platformPublicKey, 64, "\n", true)
            . "\n-----END PUBLIC KEY-----";
        $publicKey = openssl_get_publickey($key);
        if (!$publicKey) {
            throw new \RuntimeException('验签失败，平台公钥错误');
        }
        return openssl_verify($data, base64_decode($sign), $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }

    private function httpRequest(string $url, ?string $post = null, int $timeout = 10): string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($post !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            error_log("[EpayClient] HTTP request failed: {$error}, URL: {$url}");
            return '';
        }
        curl_close($ch);
        return $response;
    }
}
