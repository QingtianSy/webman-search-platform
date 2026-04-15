<?php

namespace app\service\search;

use app\repository\mongo\SearchLogDetailRepository;
use app\repository\mysql\SearchLogRepository;

class SearchLogService
{
    public function create(array $payload): array
    {
        $logNo = 'SL' . date('YmdHis') . rand(10, 99);
        $mysql = new SearchLogRepository();
        $mongo = new SearchLogDetailRepository();

        $mysql->create([
            'log_no' => $logNo,
            'keyword' => $payload['keyword'] ?? '',
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $mongo->create([
            'log_no' => $logNo,
            'payload' => $payload,
            'created_at' => time(),
        ]);

        return [
            'log_no' => $logNo,
            'saved' => true,
            'payload' => $payload,
        ];
    }
}
