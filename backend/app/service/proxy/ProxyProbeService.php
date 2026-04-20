<?php

namespace app\service\proxy;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Pool;
use GuzzleHttp\TransferStats;

class ProxyProbeService
{
    public function probe(string $protocol, string $host, int $port, ?string $username = null, ?string $password = null): array
    {
        $proxyUrl = $this->buildProxyUrl($protocol, $host, $port, $username, $password);
        $client = new Client([
            'proxy' => $proxyUrl,
            'timeout' => 10,
            'verify' => true,
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

    public function probeBatch(array $proxies, int $concurrency = 10): array
    {
        if (empty($proxies)) {
            return [];
        }

        $proxyMap = [];
        foreach ($proxies as $p) {
            $proxyMap[(int) $p['id']] = $p;
        }

        $results = [];
        $latencies = [];

        $handler = HandlerStack::create(new CurlMultiHandler());
        $requests = function () use ($proxyMap, $handler, &$latencies) {
            foreach ($proxyMap as $id => $proxy) {
                yield $id => function () use ($id, $proxy, $handler, &$latencies) {
                    $proxyUrl = $this->buildProxyUrl(
                        $proxy['protocol'], $proxy['host'], (int) $proxy['port'],
                        $proxy['username'] ?? null, $proxy['password'] ?? null
                    );
                    $client = new Client([
                        'handler' => $handler,
                        'proxy' => $proxyUrl,
                        'timeout' => 10,
                        'verify' => true,
                    ]);
                    return $client->getAsync('https://ip-api.com/json/?lang=zh-CN', [
                        'on_stats' => function (TransferStats $stats) use (&$latencies, $id) {
                            $latencies[$id] = (int) round($stats->getTransferTime() * 1000);
                        },
                    ]);
                };
            }
        };

        $pool = new Pool(new Client(['handler' => $handler]), $requests(), [
            'concurrency' => $concurrency,
            'fulfilled' => function ($response, $id) use (&$results, &$latencies) {
                $data = json_decode((string) $response->getBody(), true);
                if (($data['status'] ?? '') === 'success') {
                    $results[$id] = [
                        'success' => true,
                        'latency_ms' => $latencies[$id] ?? null,
                        'country' => $data['country'] ?? null,
                        'country_code' => $data['countryCode'] ?? null,
                        'province' => $data['regionName'] ?? null,
                        'city' => $data['city'] ?? null,
                        'exit_ip' => $data['query'] ?? null,
                    ];
                }
            },
            'rejected' => function ($reason, $id) {},
        ]);
        $pool->promise()->wait();

        $failedIds = array_diff(array_keys($proxyMap), array_keys($results));
        if (!empty($failedIds)) {
            $handler2 = HandlerStack::create(new CurlMultiHandler());
            $latencies2 = [];
            $requests2 = function () use ($failedIds, $proxyMap, $handler2, &$latencies2) {
                foreach ($failedIds as $id) {
                    $proxy = $proxyMap[$id];
                    yield $id => function () use ($id, $proxy, $handler2, &$latencies2) {
                        $proxyUrl = $this->buildProxyUrl(
                            $proxy['protocol'], $proxy['host'], (int) $proxy['port'],
                            $proxy['username'] ?? null, $proxy['password'] ?? null
                        );
                        $client = new Client([
                            'handler' => $handler2,
                            'proxy' => $proxyUrl,
                            'timeout' => 10,
                            'verify' => true,
                        ]);
                        return $client->getAsync('https://httpbin.org/ip', [
                            'on_stats' => function (TransferStats $stats) use (&$latencies2, $id) {
                                $latencies2[$id] = (int) round($stats->getTransferTime() * 1000);
                            },
                        ]);
                    };
                }
            };

            $pool2 = new Pool(new Client(['handler' => $handler2]), $requests2(), [
                'concurrency' => $concurrency,
                'fulfilled' => function ($response, $id) use (&$results, &$latencies2) {
                    $data = json_decode((string) $response->getBody(), true);
                    $ip = $data['origin'] ?? '';
                    $results[$id] = [
                        'success' => $ip !== '',
                        'latency_ms' => $ip !== '' ? ($latencies2[$id] ?? null) : null,
                        'country' => null,
                        'country_code' => null,
                        'province' => null,
                        'city' => null,
                        'exit_ip' => $ip ?: null,
                    ];
                },
                'rejected' => function ($reason, $id) use (&$results) {
                    $results[$id] = [
                        'success' => false,
                        'latency_ms' => null,
                        'country' => null,
                        'country_code' => null,
                        'province' => null,
                        'city' => null,
                        'exit_ip' => null,
                    ];
                },
            ]);
            $pool2->promise()->wait();
        }

        return $results;
    }

    protected function probeIpApi(Client $client): array
    {
        try {
            $resp = $client->get('https://ip-api.com/json/?lang=zh-CN');
            $data = json_decode((string) $resp->getBody(), true);
            if (($data['status'] ?? '') === 'success') {
                return $data;
            }
        } catch (\Throwable $e) {
            error_log("[ProxyProbe] ip-api failed: " . self::sanitizeMessage($e->getMessage()));
        }
        return [];
    }

    protected function probeHttpbin(Client $client): string
    {
        try {
            $resp = $client->get('https://httpbin.org/ip');
            $data = json_decode((string) $resp->getBody(), true);
            return $data['origin'] ?? '';
        } catch (\Throwable $e) {
            error_log("[ProxyProbe] httpbin failed: " . self::sanitizeMessage($e->getMessage()));
        }
        return '';
    }

    // cURL/Guzzle 错误会把 proxy URL 中的 user:pass@host 带进 message，写日志前先脱敏。
    private static function sanitizeMessage(string $message): string
    {
        $message = preg_replace('#([a-z][a-z0-9+.-]*://)[^/@\s]+@#i', '$1***@', $message) ?? $message;
        if (mb_strlen($message) > 200) {
            $message = mb_substr($message, 0, 200) . '...';
        }
        return $message;
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
