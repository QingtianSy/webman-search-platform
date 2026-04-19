<?php

namespace app\service\proxy;

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
        return $this->repo->list($page, $pageSize, $filters);
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
        return ['success' => $ok];
    }

    public function delete(int $id): array
    {
        $ok = $this->repo->delete($id);
        return ['success' => $ok];
    }

    public function detail(int $id): array
    {
        return $this->repo->findById($id);
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
        $added = 0;
        $failed = [];
        $probeService = new ProxyProbeService();

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

            $result = $probeService->probe(
                $parsed['protocol'],
                $parsed['host'],
                $parsed['port'],
                $parsed['username'] ?? null,
                $parsed['password'] ?? null
            );

            $probeData = [
                'latency_ms' => $result['latency_ms'],
                'status' => $result['success'] ? 1 : 2,
            ];
            if ($userProvince !== '' || $userCity !== '') {
                $probeData['country'] = '中国';
                $probeData['country_code'] = 'CN';
                $probeData['province'] = $userProvince;
                $probeData['city'] = $userCity;
            } else {
                $probeData['country'] = $result['country'];
                $probeData['country_code'] = $result['country_code'];
                $probeData['province'] = $result['province'];
                $probeData['city'] = $result['city'];
            }

            $this->repo->updateProbeResult($id, $probeData);
            $added++;
        }

        return ['added' => $added, 'failed' => $failed, 'failed_count' => count($failed)];
    }

    public function batchImport(array $items): array
    {
        $added = 0;
        $failed = 0;
        foreach ($items as $item) {
            if (empty($item['protocol']) || empty($item['host']) || empty($item['port'])) {
                $failed++;
                continue;
            }
            $id = $this->repo->create($item);
            if ($id <= 0) {
                $failed++;
                continue;
            }
            $added++;
        }
        return ['added' => $added, 'failed' => $failed];
    }

    public function batchExport(): array
    {
        return $this->repo->all();
    }

    public function probeAll(): array
    {
        $all = $this->repo->all();
        $probeService = new ProxyProbeService();
        $results = [];

        foreach ($all as $proxy) {
            $result = $probeService->probe(
                $proxy['protocol'],
                $proxy['host'],
                (int) $proxy['port'],
                $proxy['username'] ?? null,
                $proxy['password'] ?? null
            );

            $this->repo->updateProbeResult((int) $proxy['id'], [
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
}
