<?php

namespace app\common\admin;

class AdminStatusFilter
{
    public static function apply(array $list, mixed $status): array
    {
        if ($status === null || $status === '' || $status === 'all') {
            return $list;
        }

        return array_values(array_filter($list, function ($row) use ($status) {
            return (string) ($row['status'] ?? '') === (string) $status;
        }));
    }
}
