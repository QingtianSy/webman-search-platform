<?php

namespace support;

class Pagination
{
    public static function format(array $list = [], int $total = 0, int $page = 1, int $pageSize = 20): array
    {
        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }
}
