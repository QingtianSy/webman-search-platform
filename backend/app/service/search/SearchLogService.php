<?php

namespace app\service\search;

class SearchLogService
{
    public function create(array $payload): array
    {
        return [
            'log_no' => 'SL' . date('YmdHis'),
            'saved' => true,
            'payload' => $payload,
        ];
    }
}
