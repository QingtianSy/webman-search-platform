<?php

namespace app\service\admin;

use app\model\admin\Plan;
use support\Pagination;

class PlanAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['keyword' => '', 'page' => 1, 'page_size' => 20, 'sort' => '', 'order' => 'desc'];
        $page = (int) $query['page'];
        $pageSize = (int) $query['page_size'];
        $keyword = trim((string) $query['keyword']);
        $builder = Plan::query();
        if ($keyword !== '') {
            $builder->where('name', 'like', "%{$keyword}%");
        }
        $total = $builder->count();
        $list = $builder->orderBy('id', 'desc')->forPage($page, $pageSize)->get()->toArray();
        return Pagination::format($list, $total, $page, $pageSize);
    }

    public function create(array $data): array
    {
        if (isset($data['features']) && is_array($data['features'])) {
            $data['features'] = json_encode($data['features'], JSON_UNESCAPED_UNICODE);
        }
        $row = new Plan();
        $row->fill($data);
        $row->save();
        return ['success' => true, 'action' => 'create', 'id' => $row->id, 'data' => $row->toArray()];
    }

    public function update(int $id, array $data): array
    {
        $row = Plan::query()->find($id);
        if (!$row) {
            return [];
        }
        if (isset($data['features']) && is_array($data['features'])) {
            $data['features'] = json_encode($data['features'], JSON_UNESCAPED_UNICODE);
        }
        $row->fill($data);
        $row->save();
        return ['success' => true, 'action' => 'update', 'id' => $id, 'data' => $row->toArray()];
    }

    public function delete(int $id): array
    {
        $row = Plan::query()->find($id);
        if ($row) {
            $row->delete();
        }
        return ['success' => true, 'action' => 'delete', 'id' => $id];
    }
}
