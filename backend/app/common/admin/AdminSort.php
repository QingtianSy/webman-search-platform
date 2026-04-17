<?php

namespace app\common\admin;

class AdminSort
{
    public static function parse(array $input): array
    {
        $sort = trim((string) ($input['sort'] ?? ''));
        $order = strtolower(trim((string) ($input['order'] ?? 'desc')));
        if (!in_array($order, ['asc', 'desc'], true)) {
            $order = 'desc';
        }
        return [$sort, $order];
    }
}
