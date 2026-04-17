<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\common\admin\AdminSort;
use app\common\admin\AdminStatusFilter;
use app\common\admin\AdminTimeRange;
use app\repository\mysql\AnnouncementRepository;

class AnnouncementAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['keyword' => '', 'status' => null, 'page' => 1, 'page_size' => 20, 'sort' => '', 'order' => 'desc', 'start_time' => '', 'end_time' => ''];
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

    public function create(array $data): array
    {
        return (new AnnouncementRepository())->create($data);
    }

    public function update(int $id, array $data): array
    {
        return (new AnnouncementRepository())->update($id, $data);
    }

    public function delete(int $id): array
    {
        return ['deleted' => true, 'id' => $id];
    }
}
