<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\common\admin\AdminSort;
use app\common\admin\AdminTimeRange;
use app\model\admin\Announcement;
use app\repository\mysql\AnnouncementRepository;

class AnnouncementAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['keyword' => '', 'status' => null, 'page' => 1, 'page_size' => 20, 'sort' => '', 'order' => 'desc', 'start_time' => '', 'end_time' => ''];
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
        $row = new Announcement();
        $row->fill($data);
        $row->save();
        return ['success' => true, 'action' => 'create', 'id' => $row->id, 'data' => $row->toArray()];
    }

    public function update(int $id, array $data): array
    {
        $row = Announcement::query()->find($id);
        if (!$row) {
            return [];
        }
        $row->fill($data);
        $row->save();
        return ['success' => true, 'action' => 'update', 'id' => $id, 'data' => $row->toArray()];
    }

    public function delete(int $id): array
    {
        $row = Announcement::query()->find($id);
        if ($row) {
            $row->delete();
        }
        return ['success' => true, 'action' => 'delete', 'id' => $id];
    }
}
