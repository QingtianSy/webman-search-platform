<?php

namespace app\common\user;

class UserPage
{
    public static function parse(array $input): array
    {
        $page = max(1, (int) ($input['page'] ?? 1));
        $pageSize = max(1, min(100, (int) ($input['page_size'] ?? 20)));
        return [$page, $pageSize];
    }
}
