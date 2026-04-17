<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\common\admin\AdminStatusFilter;
use app\model\admin\CollectTask;
use app\repository\mysql\CollectTaskDetailRepository;
use app\repository\mysql\CollectTaskRepository;

class CollectAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['keyword' => '', 'status' => null, 'page' => 1, 'page_size' => 20];
        return config('integration.collect_source', 'mock') === 'real'
            ? $this->getListReal($query)
            : $this->getListMock($query);
    }

    protected function getListMock(array $query): array
    {
        $keyword = trim((string) $query['keyword']);
        $page = (int) $query['page'];
        $pageSize = (int) $query['page_size'];

        $list = (new CollectTaskRepository())->listByUserId(1);
        if ($keyword !== '') {
            $list = array_values(array_filter($list, function ($row) use ($keyword) {
                return str_contains((string) ($row['task_no'] ?? ''), $keyword)
                    || str_contains((string) ($row['type'] ?? ''), $keyword);
            }));
        }
        $list = AdminStatusFilter::apply($list, $query['status']);
        return AdminListBuilder::make($list, $page, $pageSize);
    }

    protected function getListReal(array $query): array
    {
        $page = (int) $query['page'];
        $pageSize = (int) $query['page_size'];
        $keyword = trim((string) $query['keyword']);
        $status = $query['status'];

        $builder = CollectTask::query();
        if ($keyword !== '') {
            $builder->where(function ($q) use ($keyword) {
                $q->where('task_no', 'like', "%{$keyword}%")
                  ->orWhere('type', 'like', "%{$keyword}%");
            });
        }
        if ($status !== null && $status !== '' && $status !== 'all') {
            $builder->where('status', $status);
        }
        $total = $builder->count();
        $list = $builder->orderBy('id', 'desc')->forPage($page, $pageSize)->get()->toArray();
        return AdminListBuilder::make($list, $page, $pageSize) + ['total' => $total];
    }

    public function detail(string $taskNo): array
    {
        return (new CollectTaskDetailRepository())->findByTaskNo($taskNo);
    }

    public function stop(string $taskNo): array
    {
        if (config('integration.collect_source', 'mock') === 'real') {
            $row = CollectTask::query()->where('task_no', $taskNo)->first();
            if ($row) {
                $row->status = 4;
                $row->error_message = '手动停止';
                $row->save();
            }
            return ['stopped' => true, 'task_no' => $taskNo];
        }
        return (new CollectTaskRepository())->updateStatus($taskNo, 4, '手动停止');
    }

    public function retry(string $taskNo): array
    {
        if (config('integration.collect_source', 'mock') === 'real') {
            $row = CollectTask::query()->where('task_no', $taskNo)->first();
            if ($row) {
                $row->status = 1;
                $row->error_message = '';
                $row->save();
            }
            return ['retried' => true, 'task_no' => $taskNo];
        }
        return (new CollectTaskRepository())->updateStatus($taskNo, 1, '');
    }
}
