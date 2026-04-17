<?php

namespace app\common\admin;

class AdminQuery
{
    public static function parse(array $input): array
    {
        [$page, $pageSize] = AdminPage::parse($input);
        [$sort, $order] = AdminSort::parse($input);
        $range = AdminTimeRange::parse($input);

        return [
            'keyword' => trim((string) ($input['keyword'] ?? '')),
            'status' => $input['status'] ?? null,
            'page' => $page,
            'page_size' => $pageSize,
            'sort' => $sort,
            'order' => $order,
            'start_time' => $range['start_time'],
            'end_time' => $range['end_time'],
        ];
    }
}
