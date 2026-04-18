<?php

namespace app\common\user;

class UserQuery
{
    public static function parse(array $input): array
    {
        [$page, $pageSize] = UserPage::parse($input);
        return [
            'keyword' => trim((string) ($input['keyword'] ?? '')),
            'type' => trim((string) ($input['type'] ?? '')),
            'status' => $input['status'] ?? null,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }
}
