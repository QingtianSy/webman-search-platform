<?php

namespace app\service\search;

use app\repository\mysql\UserApiSourceRepository;
use app\validate\user\ApiSourceValidate;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;

class ThirdPartySearchService
{
    private const MAX_PER_SOURCE_TIMEOUT = 15;

    public function startQuery(int $userId, string $keyword, string $info = '', string $split = '###'): \Closure
    {
        if ($userId <= 0) {
            return fn() => [];
        }

        $sources = (new UserApiSourceRepository())->findActiveByUserId($userId);
        if (empty($sources)) {
            return fn() => [];
        }

        $client = new Client(['verify' => true]);
        $promises = [];
        foreach ($sources as $source) {
            $promises[$source['id']] = $this->buildRequest($client, $source, $keyword, $info, $split);
        }

        return function () use ($promises, $sources) {
            return $this->collectResults($promises, $sources);
        };
    }

    public function query(int $userId, string $keyword, string $info = '', string $split = '###'): array
    {
        if ($userId <= 0) {
            return [];
        }

        $sources = (new UserApiSourceRepository())->findActiveByUserId($userId);
        if (empty($sources)) {
            return [];
        }

        $client = new Client(['verify' => true]);
        $promises = [];

        foreach ($sources as $source) {
            $promises[$source['id']] = $this->buildRequest($client, $source, $keyword, $info, $split);
        }

        return $this->collectResults($promises, $sources);
    }

    private function collectResults(array $promises, array $sources): array
    {
        $results = Promise\Utils::settle($promises)->wait();
        $apiResults = [];

        foreach ($sources as $source) {
            $id = $source['id'];
            $entry = [
                'source_id' => $id,
                'source_name' => $source['name'],
                'status' => 'error',
                'data' => null,
                'error' => null,
            ];

            if (!isset($results[$id])) {
                $entry['error'] = '请求未发送';
                $apiResults[] = $entry;
                continue;
            }

            $result = $results[$id];
            if ($result['state'] === 'rejected') {
                $entry['error'] = $result['reason'] instanceof \Throwable
                    ? $this->sanitizeErrorMessage($result['reason']->getMessage())
                    : '请求失败';
                $apiResults[] = $entry;
                continue;
            }

            $response = $result['value'];
            $httpCode = $response->getStatusCode();
            if ($httpCode < 200 || $httpCode >= 300) {
                $entry['error'] = "HTTP {$httpCode}";
                $apiResults[] = $entry;
                continue;
            }

            $body = json_decode((string) $response->getBody(), true);
            if ($body === null) {
                $entry['error'] = '响应不是有效的 JSON';
                $apiResults[] = $entry;
                continue;
            }

            $codeField = $source['success_code_field'] ?? '';
            $codeValue = $source['success_code_value'] ?? '';
            if ($codeField !== '' && $codeValue !== '') {
                $actualCode = $this->extractPath($body, $codeField);
                if ((string) $actualCode !== (string) $codeValue) {
                    $entry['error'] = "业务码不匹配: {$actualCode}";
                    $apiResults[] = $entry;
                    continue;
                }
            }

            $dataPath = $source['data_path'] ?? '';
            $data = $dataPath !== '' ? $this->extractPath($body, $dataPath) : $body;

            if (empty($data)) {
                $entry['error'] = '接口返回数据为空';
                $apiResults[] = $entry;
                continue;
            }

            $entry['status'] = 'success';
            $entry['data'] = $data;
            $apiResults[] = $entry;
        }

        return $apiResults;
    }

    private function buildRequest(Client $client, array $source, string $keyword, string $info, string $split): Promise\PromiseInterface
    {
        $method = strtoupper($source['method'] ?? 'GET');
        $url = $source['url'] ?? '';
        $timeout = (int) ($source['timeout'] ?? 10);
        if ($timeout <= 0 || $timeout > self::MAX_PER_SOURCE_TIMEOUT) {
            $timeout = self::MAX_PER_SOURCE_TIMEOUT;
        }

        $queryParams = [];
        $bodyParams = [];

        $kwParam = $source['keyword_param'] ?? 'q';
        $kwPosition = $source['keyword_position'] ?? 'url_param';
        if ($kwPosition === 'body') {
            $bodyParams[$kwParam] = $keyword;
        } else {
            $queryParams[$kwParam] = $keyword;
        }

        $typeParam = $source['type_param'] ?? '';
        if ($typeParam !== '' && $info !== '') {
            $typeValue = $this->formatTypeValue($source, $info, $split);
            $typePosition = $source['type_position'] ?? 'url_param';
            if ($typePosition === 'body') {
                $bodyParams[$typeParam] = $typeValue;
            } else {
                $queryParams[$typeParam] = $typeValue;
            }
        }

        $extraConfig = $source['extra_config'] ?? '';
        if ($extraConfig !== '' && is_string($extraConfig)) {
            $extra = json_decode($extraConfig, true);
            if (is_array($extra)) {
                foreach ($extra as $k => $v) {
                    if ($method === 'GET') {
                        $queryParams[$k] = $v;
                    } else {
                        $bodyParams[$k] = $v;
                    }
                }
            }
        }

        $headers = [];
        $headersRaw = $source['headers'] ?? '';
        if ($headersRaw !== '' && is_string($headersRaw)) {
            $decoded = json_decode($headersRaw, true);
            if (is_array($decoded)) {
                $headers = $decoded;
            }
        }

        $options = [
            'timeout' => $timeout,
            'headers' => $headers,
            'allow_redirects' => false,
        ];

        if (!empty($queryParams)) {
            $options['query'] = $queryParams;
        }

        if ($method === 'POST' && !empty($bodyParams)) {
            $contentType = $headers['Content-Type'] ?? $headers['content-type'] ?? '';
            if (stripos($contentType, 'json') !== false) {
                $options['json'] = $bodyParams;
            } else {
                $options['form_params'] = $bodyParams;
            }
        }

        try {
            $resolved = ApiSourceValidate::resolveToSafeIp($url);
        } catch (\Throwable $e) {
            return Promise\Create::rejectionFor($e);
        }

        $options['curl'] = [
            CURLOPT_RESOLVE => ["{$resolved['host']}:{$resolved['port']}:{$resolved['ip']}"],
        ];

        return $client->requestAsync($method, $url, $options);
    }

    private function formatTypeValue(array $source, string $info, string $split): string
    {
        $format = $source['option_format'] ?? '';
        $delimiter = $source['option_delimiter'] ?? '###';

        if ($format === '' || $info === '') {
            return $info;
        }

        $parts = explode($delimiter ?: $split, $info);
        $type = $parts[0] ?? '';
        $options = array_slice($parts, 1);
        $optionsStr = implode($delimiter ?: $split, $options);

        $result = str_replace('[type]', $type, $format);
        $result = str_replace('[options]', $optionsStr, $result);

        return $result;
    }

    private function extractPath(array $data, string $path)
    {
        $keys = explode('.', $path);
        $current = $data;
        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return null;
            }
            $current = $current[$key];
        }
        return $current;
    }

    // Guzzle 异常可能把 URL（含 user:pass@host）和 Authorization 头带进 message，
    // 这里统一脱敏并截断，避免泄漏到 api_results.error 与日志。
    private function sanitizeErrorMessage(string $message): string
    {
        $message = preg_replace('#([a-z][a-z0-9+.-]*://)[^/@\s]+@#i', '$1***@', $message) ?? $message;
        $message = preg_replace('/(Authorization|X-[A-Za-z0-9-]*Key|X-[A-Za-z0-9-]*Token|Cookie)\s*:\s*[^\r\n]*/i', '$1: ***', $message) ?? $message;
        if (mb_strlen($message) > 200) {
            $message = mb_substr($message, 0, 200) . '...';
        }
        return $message;
    }
}
