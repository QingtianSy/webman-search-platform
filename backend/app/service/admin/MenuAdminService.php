<?php

namespace app\service\admin;

use app\exception\BusinessException;
use app\model\admin\Menu;
use support\Pagination;
use support\ResponseCode;

class MenuAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['keyword' => '', 'status' => null, 'page' => 1, 'page_size' => 20, 'sort' => '', 'order' => 'desc'];
        $page = (int) $query['page'];
        $pageSize = (int) $query['page_size'];
        $keyword = trim((string) $query['keyword']);
        $status = $query['status'];
        $sort = trim((string) $query['sort']);
        $order = strtolower((string) $query['order']) === 'asc' ? 'asc' : 'desc';
        $sortable = ['id', 'name', 'path', 'permission_code', 'sort', 'status', 'created_at', 'updated_at'];
        if (!in_array($sort, $sortable, true)) {
            $sort = 'sort';
        }
        $builder = Menu::query();
        if ($keyword !== '') {
            $builder->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('path', 'like', "%{$keyword}%")
                  ->orWhere('permission_code', 'like', "%{$keyword}%");
            });
        }
        if ($status !== null && $status !== '' && $status !== 'all') {
            $builder->where('status', $status);
        }
        $total = $builder->count();
        $list = $builder->orderBy($sort, $order)->orderBy('id', 'asc')->forPage($page, $pageSize)->get()->toArray();
        return Pagination::format($list, $total, $page, $pageSize);
    }

    public function create(array $data): array
    {
        $row = new Menu();
        $row->fill($data);
        $row->save();
        return ['success' => true, 'action' => 'create', 'id' => $row->id, 'data' => $row->toArray()];
    }

    public function update(int $id, array $data): array
    {
        $row = Menu::query()->find($id);
        if (!$row) {
            throw new BusinessException('菜单不存在', 40001);
        }
        unset($data['id']);
        $row->fill($data);
        $row->save();
        return ['success' => true, 'action' => 'update', 'id' => $id, 'data' => $row->toArray()];
    }

    public function delete(int $id): array
    {
        if (Menu::query()->where('parent_id', $id)->exists()) {
            throw new BusinessException('该菜单下存在子菜单，无法删除', ResponseCode::PARAM_ERROR);
        }
        $row = Menu::query()->find($id);
        if (!$row) {
            throw new BusinessException('菜单不存在', 40001);
        }
        $row->delete();
        return ['success' => true, 'action' => 'delete', 'id' => $id];
    }
}
