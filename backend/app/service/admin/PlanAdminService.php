<?php

namespace app\service\admin;

use app\exception\BusinessException;
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
        if (Plan::query()->where('code', $data['code'] ?? '')->exists()) {
            throw new BusinessException('套餐编码已存在', 40001);
        }
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
            throw new BusinessException('套餐不存在', 40001);
        }
        if (!empty($data['code']) && $data['code'] !== $row->code) {
            if (Plan::query()->where('code', $data['code'])->where('id', '!=', $id)->exists()) {
                throw new BusinessException('套餐编码已存在', 40001);
            }
        }
        if (isset($data['features']) && is_array($data['features'])) {
            $data['features'] = json_encode($data['features'], JSON_UNESCAPED_UNICODE);
        }
        unset($data['id']);
        $row->fill($data);
        $row->save();
        return ['success' => true, 'action' => 'update', 'id' => $id, 'data' => $row->toArray()];
    }

    public function delete(int $id): array
    {
        $row = Plan::query()->find($id);
        if (!$row) {
            throw new BusinessException('套餐不存在', 40001);
        }
        $row->delete();
        return ['success' => true, 'action' => 'delete', 'id' => $id];
    }
}
