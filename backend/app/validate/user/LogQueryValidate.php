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
            'page' => max(1, (int) ($data['page'] ?? 1)),
            'page_size' => max(1, min(100, (int) ($data['page_size'] ?? 20))),
        ];
    }
}
