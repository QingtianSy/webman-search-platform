<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\common\admin\AdminSort;
use app\common\admin\AdminTimeRange;

class SearchLogAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['keyword' => '', 'page' => 1, 'page_size' => 20, 'sort' => '', 'order' => 'desc', 'start_time' => '', 'end_time' => ''];
        $keyword = trim((string) $query['keyword']);
        $page = (int) $query['page'];
        $pageSize = (int) $query['page_size'];
        [$sort, $order] = AdminSort::parse($query);
        $range = AdminTimeRange::parse($query);

        $file = dirname(__DIR__, 3) . '/storage/logs/search_logs.jsonl';
        $list = [];
        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $decoded = json_decode($line, true);
                if (is_array($decoded)) {
                    $list[] = $decoded;
                }
            }
        }
        if ($keyword !== '') {
            $list = array_values(array_filter($list, function ($row) use ($keyword) {
                return str_contains((string) ($row['keyword'] ?? ''), $keyword)
                    || str_contains((string) ($row['log_no'] ?? ''), $keyword);
            }));
        }
        if ($range['start_time'] !== '') {
            $list = array_values(array_filter($list, fn($row) => (string)($row['created_at'] ?? '') >= $range['start_time']));
        }
        if ($range['end_time'] !== '') {
            $list = array_values(array_filter($list, fn($row) => (string)($row['created_at'] ?? '') <= $range['end_time']));
        }
        if ($sort !== '') {
            usort($list, function ($a, $b) use ($sort, $order) {
                $av = $a[$sort] ?? null;
                $bv = $b[$sort] ?? null;
                return $order === 'asc' ? ($av <=> $bv) : ($bv <=> $av);
            });
        }
        return AdminListBuilder::make($list, $page, $pageSize);
    }
}
