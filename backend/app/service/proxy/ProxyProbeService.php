<?php

namespace app\service\proxy;

use GuzzleHttp\Client;

class ProxyProbeService
{
    public function probe(string $protocol, string $host, int $port, ?string $username = null, ?string $password = null): array
    {
        $proxyUrl = $this->buildProxyUrl($protocol, $host, $port, $username, $password);
        $client = new Client([
            'proxy' => $proxyUrl,
            'timeout' => 10,
            'verify' => false,
        ]);

        $start = microtime(true);
        $geo = $this->probeIpApi($client);
        $latencyMs = (int) round((microtime(true) - $start) * 1000);

        if (empty($geo)) {
            $start2 = microtime(true);
            $ip = $this->probeHttpbin($client);
            $latencyMs = (int) round((microtime(true) - $start2) * 1000);
            if ($ip === '') {
                return [
                    'success' => false,
                    'latency_ms' => null,
                    'country' => null,
                    'country_code' => null,
                    'province' => null,
                    'city' => null,
                    'exit_ip' => null,
                ];
            }
            return [
                'success' => true,
                'latency_ms' => $latencyMs,
                'country' => null,
                'country_code' => null,
                'province' => null,
                'city' => null,
                'exit_ip' => $ip,
            ];
        }

        return [
            'success' => true,
            'latency_ms' => $latencyMs,
            'country' => $geo['country'] ?? null,
            'country_code' => $geo['countryCode'] ?? null,
            'province' => $geo['regionName'] ?? null,
            'city' => $geo['city'] ?? null,
            'exit_ip' => $geo['query'] ?? null,
        ];
    }

    protected function probeIpApi(Client $client): array
    {
        try {
            $resp = $client->get('http://ip-api.com/json/?lang=zh-CN');
            $data = json_decode((string) $resp->getBody(), true);
            if (($data['status'] ?? '') === 'success') {
                return $data;
            }
        } catch (\Throwable $e) {
            error_log("[ProxyProbe] ip-api failed: " . $e->getMessage());
        }
        return [];
    }

    protected function probeHttpbin(Client $client): string
    {
        try {
            $resp = $client->get('http://httpbin.org/ip');
            $data = json_decode((string) $resp->getBody(), true);
            return $data['origin'] ?? '';
        } catch (\Throwable $e) {
            error_log("[ProxyProbe] httpbin failed: " . $e->getMessage());
        }
        return '';
    }

    protected function buildProxyUrl(string $protocol, string $host, int $port, ?string $username, ?string $password): string
    {
        $auth = '';
        if ($username !== null && $username !== '') {
            $auth = urlencode($username);
            if ($password !== null && $password !== '') {
                $auth .= ':' . urlencode($password);
            }
            $auth .= '@';
        }
        return "{$protocol}://{$auth}{$host}:{$port}";
    }
}
