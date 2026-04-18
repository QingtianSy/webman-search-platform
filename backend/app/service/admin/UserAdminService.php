<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\common\admin\AdminStatusFilter;
use app\model\admin\User;
use app\repository\mysql\UserRepository;

class UserAdminService
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

        $list = (new UserRepository())->all();
        foreach ($list as &$row) {
            unset($row['password'], $row['password_hash'], $row['type']);
        }

        if ($keyword !== '') {
            $list = array_values(array_filter($list, function ($row) use ($keyword) {
                return str_contains((string) ($row['username'] ?? ''), $keyword)
                    || str_contains((string) ($row['nickname'] ?? ''), $keyword)
                    || str_contains((string) ($row['mobile'] ?? ''), $keyword)
                    || str_contains((string) ($row['email'] ?? ''), $keyword);
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
        $sortable = ['id', 'username', 'nickname', 'mobile', 'email', 'status', 'created_at', 'updated_at'];
        if (!in_array($sort, $sortable, true)) {
            $sort = 'id';
        }

        $builder = User::query();
        if ($keyword !== '') {
            $builder->where(function ($q) use ($keyword) {
                $q->where('username', 'like', "%{$keyword}%")
                    ->orWhere('nickname', 'like', "%{$keyword}%")
                    ->orWhere('mobile', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }
        if ($status !== null && $status !== '' && $status !== 'all') {
            $builder->where('status', $status);
        }
        $total = $builder->count();
        $list = $builder->orderBy($sort, $order)
            ->forPage($page, $pageSize)
            ->get()
            ->makeHidden(['password', 'password_hash'])
            ->toArray();
        foreach ($list as &$row) {
            unset($row['type']);
        }
        return AdminListBuilder::make($list, $page, $pageSize + 0) + ['total' => $total];
    }
}
