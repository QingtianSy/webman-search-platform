<?php

namespace app\service\admin;

use app\common\admin\AdminSort;
use app\common\admin\AdminTimeRange;
use app\model\admin\SearchLog;
use support\Pagination;

class SearchLogAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['keyword' => '', 'page' => 1, 'page_size' => 20, 'sort' => '', 'order' => 'desc', 'start_time' => '', 'end_time' => ''];
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
        return Pagination::format($list, $total, $page, $pageSize);
    }

    public function export(array $query = []): array
    {
        $query += ['keyword' => '', 'sort' => '', 'order' => 'desc', 'start_time' => '', 'end_time' => ''];
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

        $list = $builder->orderBy($sort, $order)->limit(50000)->get()->toArray();

        $headers = ['日志编号', '用户ID', '关键词', '题型', '状态', '命中数', '来源', '消耗额度', '耗时(ms)', '创建时间'];
        $rows = array_map(fn($r) => [
            $r['log_no'] ?? '',
            $r['user_id'] ?? '',
            $r['keyword'] ?? '',
            $r['question_type'] ?? '',
            ((int) ($r['status'] ?? 0)) === 1 ? '成功' : '失败',
            $r['hit_count'] ?? 0,
            $r['source_type'] ?? '',
            $r['consume_quota'] ?? 0,
            $r['cost_ms'] ?? 0,
            $r['created_at'] ?? '',
        ], $list);

        return [$headers, $rows];
    }
}
