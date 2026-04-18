<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\common\admin\AdminStatusFilter;
use app\model\admin\Menu;
use app\repository\mysql\MenuRepository;

class MenuAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['keyword' => '', 'status' => null, 'page' => 1, 'page_size' => 20, 'sort' => '', 'order' => 'desc'];
        return config('integration.auth_rbac_source', 'mock') === 'real'
            ? $this->getListReal($query)
            : $this->getListMock($query);
    }

    protected function getListMock(array $query): array
    {
        $keyword = trim((string) $query['keyword']);
        $page = (int) $query['page'];
        $pageSize = (int) $query['page_size'];
        $list = (new MenuRepository())->all();
        if ($keyword !== '') {
            $list = array_values(array_filter($list, function ($row) use ($keyword) {
                return str_contains((string) ($row['name'] ?? ''), $keyword)
                    || str_contains((string) ($row['path'] ?? ''), $keyword)
                    || str_contains((string) ($row['permission_code'] ?? ''), $keyword);
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
        $sort = trim((string) $query['sort']);
        $order = strtolower((string) $query['order']) === 'asc' ? 'asc' : 'desc';
        $sortable = ['id', 'name', 'path', 'permission_code', 'sort', 'status', 'created_at', 'updated_at'];
        if (!in_array($sort, $sortable, true)) {
            $sort = 'id';
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
        $list = $builder->orderBy($sort, $order)->forPage($page, $pageSize)->get()->toArray();
        return AdminListBuilder::make($list, $page, $pageSize) + ['total' => $total];
    }
}
