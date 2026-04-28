<?php

namespace app\validate\user;

class LogQueryValidate
{
    public function list(array $data): array
    {
        return [
            'keyword' => trim((string) ($data['keyword'] ?? '')),
            'type' => trim((string) ($data['type'] ?? '')),
            'status' => $data['status'] ?? null,
            'order_no' => trim((string) ($data['order_no'] ?? '')),
            'pay_method' => trim((string) ($data['pay_method'] ?? '')),
            'date_from' => trim((string) ($data['date_from'] ?? '')),
            'date_to' => trim((string) ($data['date_to'] ?? '')),
            'page' => max(1, (int) ($data['page'] ?? 1)),
            'page_size' => max(1, min(100, (int) ($data['page_size'] ?? 20))),
        ];
    }
}
