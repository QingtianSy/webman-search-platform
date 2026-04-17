<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\common\admin\AdminSort;
use app\common\admin\AdminTimeRange;
use app\model\admin\SearchLog;

class SearchLogAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['keyword' => '', 'page' => 1, 'page_size' => 20, 'sort' => '', 'order' => 'desc', 'start_time' => '', 'end_time' => ''];
        return config('integration.log_source', 'mock') === 'real'
            ? $this->getListReal($query)
            : $this->getListMock($query);
    }

    protected function getListMock(array $query): array
    {
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

    protected function getListReal(array $query): array
    {
        $page = (int) $query['page'];
        $pageSize = (int) $query['page_size'];
        $keyword = trim((string) $query['keyword']);
        [$sort, $order] = AdminSort::parse($query);
        $range = AdminTimeRange::parse($query);
        $sortable = ['id', 'log_no', 'keyword', 'created_at'];
        if (!in_array($sort, $sortable, true)) {
            $sort = 'id';
        }

        $builder = SearchLog::query();
        if ($keyword !== '') {
            $builder->where(function ($q) use ($keyword) {
                $q->where('keyword', 'like', "%{$keyword}%")
                  ->orWhere('log_no', 'like', "%{$keyword}%");
            });
        }
        if ($range['start_time'] !== '') {
            $builder->where('created_at', '>=', $range['start_time']);
        }
        if ($range['end_time'] !== '') {
            $builder->where('created_at', '<=', $range['end_time']);
        }
        $total = $builder->count();
        $list = $builder->orderBy($sort, $order)->forPage($page, $pageSize)->get()->toArray();
        return AdminListBuilder::make($list, $page, $pageSize) + ['total' => $total];
    }
}
