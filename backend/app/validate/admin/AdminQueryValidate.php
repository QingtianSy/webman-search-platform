<?php

namespace app\validate\admin;

class AdminQueryValidate
{
    public function list(array $data): array
    {
        return [
            'keyword' => trim((string) ($data['keyword'] ?? '')),
            'status' => $data['status'] ?? null,
            'page' => max(1, (int) ($data['page'] ?? 1)),
            'page_size' => max(1, min(100, (int) ($data['page_size'] ?? 20))),
            'sort' => trim((string) ($data['sort'] ?? '')),
            'order' => strtolower(trim((string) ($data['order'] ?? 'desc'))),
            'start_time' => trim((string) ($data['start_time'] ?? '')),
            'end_time' => trim((string) ($data['end_time'] ?? '')),
        ];
    }
}
