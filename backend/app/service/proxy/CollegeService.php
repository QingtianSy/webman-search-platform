<?php

namespace app\service\proxy;

use GuzzleHttp\Client;

class CollegeService
{
    public function lookup(string $schoolName): array
    {
        if ($schoolName === '') {
            return ['province' => '', 'city' => ''];
        }

        try {
            $client = new Client(['timeout' => 5, 'verify' => true]);
            $resp = $client->get('https://api.pearktrue.cn/api/college/', [
                'query' => ['keyword' => $schoolName],
            ]);
            $data = json_decode((string) $resp->getBody(), true);
            if (($data['code'] ?? 0) !== 200 || empty($data['data'])) {
                return ['province' => '', 'city' => ''];
            }

            $first = $data['data'][0] ?? [];
            return [
                'province' => $first['province'] ?? '',
                'city' => $first['city'] ?? '',
            ];
        } catch (\Throwable $e) {
            error_log("[CollegeService] lookup failed for {$schoolName}: " . $e->getMessage());
            return ['province' => '', 'city' => ''];
        }
    }
}
