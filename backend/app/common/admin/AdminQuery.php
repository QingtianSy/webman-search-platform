<?php

namespace app\common\admin;

class AdminQuery
{
    public static function parse(array $input): array
    {
        [$page, $pageSize] = AdminPage::parse($input);
        return [
            'keyword' => trim((string) ($input['keyword'] ?? '')),
            'status' => $input['status'] ?? null,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }
}
