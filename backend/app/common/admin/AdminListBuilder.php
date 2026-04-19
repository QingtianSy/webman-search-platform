<?php

namespace app\common\admin;

use support\Pagination;

class AdminListBuilder
{
    public static function make(array $list, int $page = 1, int $pageSize = 20): array
    {
        $total = count($list);
        $offset = ($page - 1) * $pageSize;
        $sliced = array_slice($list, $offset, $pageSize);
        return Pagination::format($sliced, $total, $page, $pageSize);
    }
}
