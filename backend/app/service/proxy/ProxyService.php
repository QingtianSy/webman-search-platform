<?php

namespace app\service\proxy;

use app\exception\BusinessException;
use app\repository\mysql\ProxyRepository;

class ProxyService
{
    protected ProxyRepository $repo;

    public function __construct()
    {
        $this->repo = new ProxyRepository();
    }

    public function list(array $query): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($query['page_size'] ?? 20)));
        $filters = [];
        if (!empty($query['protocol'])) {
            $filters['protocol'] = $query['protocol'];
        }
        if (isset($query['status']) && $query['status'] !== '') {
            $filters['status'] = $query['status'];
        }
        if (!empty($query['keyword'])) {
            $filters['keyword'] = $query['keyword'];
        }
        $result = $this->repo->list($page, $pageSize, $filters);
        $result['list'] = array_map([self::class, 'maskCredentials'], $result['list']);
        return $result;
    }

    public function create(array $data): array
    {
        $id = $this->repo->create($data);
        if ($id <= 0) {
            return ['success' => false, 'msg' => '创建失败'];
        }
        return ['success' => true, 'id' => $id];
    }

    public function update(int $id, array $data): array
    {
        $ok = $this->repo->update($id, $data);
        if (!$ok) {
            throw new BusinessException('代理不存在', 40001);
        }
        return ['success' => true];
    }

    public function delete(int $id): array
    {
        $ok = $this->repo->delete($id);
        if (!$ok) {
            throw new BusinessException('代理不存在', 40001);
        }
        return ['success' => true];
    }

    public function detail(int $id): array
    {
        return self::maskCredentials($this->repo->findById($id));
    }

    public function probe(int $id): array
    {
        $proxy = $this->repo->findById($id);
        if (empty($proxy)) {
            return ['success' => false, 'msg' => '代理不存在'];
        }

        $probeService = new ProxyProbeService();
        $result = $probeService->probe(
            $proxy['protocol'],
            $proxy['host'],
            (int) $proxy['port'],
            $proxy['username'] ?? null,
            $proxy['password'] ?? null
        );

        $status = $result['success'] ? 1 : 2;
        $this->repo->updateProbeResult($id, [
            'country' => $result['country'],
            'country_code' => $result['country_code'],
            'province' => $result['province'],
            'city' => $result['city'],
            'latency_ms' => $result['latency_ms'],
            'status' => $status,
        ]);

        $result['status'] = $status;
        return $result;
    }

    public function quickAdd(string $rawLines): array
    {
        $lines = array_filter(array_map('trim', explode("\n", $rawLines)));
        $inserted = [];
        $failed = [];

        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', $line, 2);
            $urlPart = $parts[0];
            $locationPart = $parts[1] ?? '';

            $parsed = $this->parseProxyUrl($urlPart);
            if ($parsed === null) {
                $failed[] = $line;
                continue;
            }

            $userProvince = '';
            $userCity = '';
            if ($locationPart !== '') {
                $loc = $this->parseLocation($locationPart);
                $userProvince = $loc['province'];
                $userCity = $loc['city'];
                if ($userProvince !== '' || $userCity !== '') {
                    $parsed['name'] = trim($userProvince . $userCity);
                    $parsed['province'] = $userProvince;
                    $parsed['city'] = $userCity;
                    $parsed['country'] = '中国';
                    $parsed['country_code'] = 'CN';
                }
            }

            $id = $this->repo->create($parsed);
            if ($id <= 0) {
                $failed[] = $line;
                continue;
            }

            $inserted[] = [
                'id' => $id,
                'protocol' => $parsed['protocol'],
                'host' => $parsed['host'],
                'port' => $parsed['port'],
                'username' => $parsed['username'] ?? null,
                'password' => $parsed['password'] ?? null,
                'userProvince' => $userProvince,
                'userCity' => $userCity,
            ];
        }

        if (!empty($inserted)) {
            $probeResults = (new ProxyProbeService())->probeBatch($inserted);

            foreach ($inserted as $rec) {
                $id = $rec['id'];
                $result = $probeResults[$id] ?? [
                    'success' => false, 'latency_ms' => null,
                    'country' => null, 'country_code' => null,
                    'province' => null, 'city' => null,
                ];

                $probeData = [
                    'latency_ms' => $result['latency_ms'],
                    'status' => $result['success'] ? 1 : 2,
                ];
                if ($rec['userProvince'] !== '' || $rec['userCity'] !== '') {
                    $probeData['country'] = '中国';
                    $probeData['country_code'] = 'CN';
                    $probeData['province'] = $rec['userProvince'];
                    $probeData['city'] = $rec['userCity'];
                } else {
                    $probeData['country'] = $result['country'];
                    $probeData['country_code'] = $result['country_code'];
                    $probeData['province'] = $result['province'];
                    $probeData['city'] = $result['city'];
                }

                $this->repo->updateProbeResult($id, $probeData);
            }
        }

        return ['added' => count($inserted), 'failed' => $failed, 'failed_count' => count($failed)];
    }

    public function batchImport(array $items): array
    {
        $valid = [];
        $failed = 0;
        foreach ($items as $item) {
            if (empty($item['protocol']) || empty($item['host']) || empty($item['port'])) {
                $failed++;
                continue;
            }
            $valid[] = $item;
        }
        $added = !empty($valid) ? $this->repo->batchCreate($valid) : 0;
        return ['added' => $added, 'failed' => $failed];
    }

    public function batchExport(): array
    {
        return array_map([self::class, 'maskCredentials'], $this->repo->all());
    }

    public function probeAll(): array
    {
        $all = $this->repo->all();
        if (empty($all)) {
            return [];
        }

        $probeResults = (new ProxyProbeService())->probeBatch($all);
        $results = [];

        foreach ($all as $proxy) {
            $id = (int) $proxy['id'];
            $result = $probeResults[$id] ?? [
                'success' => false, 'latency_ms' => null,
                'country' => null, 'country_code' => null,
                'province' => null, 'city' => null, 'exit_ip' => null,
            ];

            $this->repo->updateProbeResult($id, [
                'country' => $result['country'],
                'country_code' => $result['country_code'],
                'province' => $result['province'],
                'city' => $result['city'],
                'latency_ms' => $result['latency_ms'],
                'status' => $result['success'] ? 1 : 2,
            ]);

            $results[] = ['id' => $proxy['id'], 'host' => $proxy['host'], 'result' => $result];
        }

        return $results;
    }

    protected function parseLocation(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return ['province' => '', 'city' => ''];
        }

        $parts = preg_split('/\s+/', $text, 2);
        if (count($parts) === 2) {
            return ['province' => $parts[0], 'city' => $parts[1]];
        }

        $provinces = [
            '黑龙江', '内蒙古',
            '北京', '天津', '上海', '重庆',
            '河北', '山西', '辽宁', '吉林', '江苏', '浙江', '安徽', '福建',
            '江西', '山东', '河南', '湖北', '湖南', '广东', '海南', '四川',
            '贵州', '云南', '陕西', '甘肃', '青海', '台湾',
            '广西', '西藏', '宁夏', '新疆', '香港', '澳门',
        ];

        foreach ($provinces as $prov) {
            if (str_starts_with($text, $prov)) {
                $city = mb_substr($text, mb_strlen($prov));
                return ['province' => $prov, 'city' => $city];
            }
        }

        return ['province' => $text, 'city' => ''];
    }

    protected function parseProxyUrl(string $url): ?array
    {
        $url = trim($url);
        if (!preg_match('#^(https?|socks5h?|HTTPS?|SOCKS5H?)://#', $url)) {
            $url = 'http://' . $url;
        }

        $parsed = parse_url($url);
        if (empty($parsed['host'])) {
            return null;
        }

        $protocol = strtolower($parsed['scheme'] ?? 'http');
        return [
            'name' => $parsed['host'],
            'protocol' => $protocol,
            'host' => $parsed['host'],
            'port' => (int) ($parsed['port'] ?? ($protocol === 'https' ? 443 : 8080)),
            'username' => isset($parsed['user']) ? urldecode($parsed['user']) : null,
            'password' => isset($parsed['pass']) ? urldecode($parsed['pass']) : null,
        ];
    }

    private static function maskCredentials(array $row): array
    {
        if (isset($row['password']) && $row['password'] !== '' && $row['password'] !== null) {
            $row['password'] = '****';
        }
        if (isset($row['username']) && $row['username'] !== '' && $row['username'] !== null) {
            $row['username'] = '****';
        }
        return $row;
    }
}
