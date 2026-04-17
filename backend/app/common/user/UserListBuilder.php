<?php

namespace app\common\user;

use support\Pagination;

class UserListBuilder
{
    public static function make(array $list, int $page = 1, int $pageSize = 20): array
    {
        return Pagination::format($list, count($list), $page, $pageSize);
    }
}
