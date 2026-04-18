<?php

namespace app\common\admin;

use support\Pagination;

class AdminListBuilder
{
    public static function make(array $list, int $page = 1, int $pageSize = 20): array
    {
        return Pagination::format($list, count($list), $page, $pageSize);
    }
}
