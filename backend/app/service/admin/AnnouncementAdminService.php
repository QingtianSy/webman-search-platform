<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\common\admin\AdminSort;
use app\common\admin\AdminStatusFilter;
use app\common\admin\AdminTimeRange;
use app\model\admin\Announcement;
use app\repository\mysql\AnnouncementRepository;

class AnnouncementAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['keyword' => '', 'status' => null, 'page' => 1, 'page_size' => 20, 'sort' => '', 'order' => 'desc', 'start_time' => '', 'end_time' => ''];
        return config('integration.config_source', 'mock') === 'real'
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

        $list = (new AnnouncementRepository())->latest();
        if ($keyword !== '') {
            $list = array_values(array_filter($list, function ($row) use ($keyword) {
                return str_contains((string) ($row['title'] ?? ''), $keyword)
                    || str_contains((string) ($row['content'] ?? ''), $keyword);
            }));
        }
        $list = AdminStatusFilter::apply($list, $query['status']);
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
        $status = $query['status'];
        [$sort, $order] = AdminSort::parse($query);
        $range = AdminTimeRange::parse($query);

        $sortable = ['id', 'title', 'status', 'created_at', 'updated_at'];
        if (!in_array($sort, $sortable, true)) {
            $sort = 'id';
        }

        $builder = Announcement::query();
        if ($keyword !== '') {
            $builder->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('content', 'like', "%{$keyword}%");
            });
        }
        if ($status !== null && $status !== '' && $status !== 'all') {
            $builder->where('status', $status);
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

    public function create(array $data): array
    {
        if (config('integration.config_source', 'mock') === 'real') {
            $row = new Announcement();
            $row->fill($data);
            $row->save();
            return ['success' => true, 'action' => 'create', 'id' => $row->id, 'data' => $row->toArray()];
        }
        $row = (new AnnouncementRepository())->create($data);
        return ['success' => true, 'action' => 'create', 'id' => $row['id'] ?? null, 'data' => $row];
    }

    public function update(int $id, array $data): array
    {
        if (config('integration.config_source', 'mock') === 'real') {
            $row = Announcement::query()->find($id);
            if (!$row) {
                return [];
            }
            $row->fill($data);
            $row->save();
            return ['success' => true, 'action' => 'update', 'id' => $id, 'data' => $row->toArray()];
        }
        $row = (new AnnouncementRepository())->update($id, $data);
        return ['success' => true, 'action' => 'update', 'id' => $id, 'data' => $row];
    }

    public function delete(int $id): array
    {
        if (config('integration.config_source', 'mock') === 'real') {
            $row = Announcement::query()->find($id);
            if ($row) {
                $row->delete();
            }
            return ['success' => true, 'action' => 'delete', 'id' => $id];
        }
        (new AnnouncementRepository())->delete($id);
        return ['success' => true, 'action' => 'delete', 'id' => $id];
    }
}
