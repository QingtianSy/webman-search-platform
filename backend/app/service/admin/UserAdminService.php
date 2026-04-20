<?php

namespace app\service\admin;

use app\exception\BusinessException;
use app\model\admin\User;
use app\repository\redis\TokenCacheRepository;
use support\Db;
use support\Pagination;

class UserAdminService
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

        $userIds = array_column($list, 'id');
        $roleMap = $this->getRoleMap($userIds);
        foreach ($list as &$row) {
            unset($row['type']);
            $row['roles'] = $roleMap[(int) $row['id']] ?? [];
        }
        return Pagination::format($list, $total, $page, $pageSize);
    }

    public function create(array $data): array
    {
        $row = new User();
        $row->username = $data['username'];
        $row->password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $row->nickname = $data['nickname'] ?? '';
        $row->mobile = $data['mobile'] ?? '';
        $row->email = $data['email'] ?? '';
        $row->status = $data['status'] ?? 1;
        $row->save();
        return ['success' => true, 'action' => 'create', 'id' => $row->id, 'data' => $row->toArray()];
    }

    public function update(int $id, array $data): array
    {
        $row = User::query()->find($id);
        if (!$row) {
            throw new BusinessException('用户不存在', 40001);
        }

        return Db::transaction(function () use ($row, $id, $data) {
            if (!empty($data['username'])) {
                $row->username = $data['username'];
            }
            if (!empty($data['password'])) {
                $row->password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            if (isset($data['nickname'])) {
                $row->nickname = $data['nickname'];
            }
            if (isset($data['mobile'])) {
                $row->mobile = $data['mobile'];
            }
            if (isset($data['email'])) {
                $row->email = $data['email'];
            }
            if (isset($data['status'])) {
                $row->status = $data['status'];
            }
            $row->save();

            if (isset($data['role_ids'])) {
                $this->syncRoles($id, $data['role_ids']);
            }

            if (!empty($data['password']) || (isset($data['status']) && (int) $data['status'] === 0) || isset($data['role_ids'])) {
                (new TokenCacheRepository())->setUserToken($id, 'REVOKED');
            }

            return ['success' => true, 'action' => 'update', 'id' => $id, 'data' => $row->toArray()];
        });
    }

    public function delete(int $id): array
    {
        $row = User::query()->find($id);
        if (!$row) {
            throw new BusinessException('用户不存在', 40001);
        }
        (new TokenCacheRepository())->setUserToken($id, 'REVOKED');
        $row->delete();
        Db::table('user_role')->where('user_id', $id)->delete();
        Db::table('user_api_keys')->where('user_id', $id)->delete();
        return ['success' => true, 'action' => 'delete', 'id' => $id];
    }

    public function toggleStatus(int $id): array
    {
        $row = User::query()->find($id);
        if (!$row) {
            throw new BusinessException('用户不存在', 40001);
        }
        $row->status = $row->status == 1 ? 0 : 1;
        $row->save();

        if ((int) $row->status === 0) {
            (new TokenCacheRepository())->setUserToken($id, 'REVOKED');
            Db::table('user_api_keys')->where('user_id', $id)->update(['status' => 0]);
        }

        return ['success' => true, 'action' => 'toggle_status', 'id' => $id, 'status' => $row->status];
    }

    public function assignRoles(int $userId, array $roleIds): array
    {
        $user = User::query()->find($userId);
        if (!$user) {
            throw new BusinessException('用户不存在', 40001);
        }
        $this->syncRoles($userId, $roleIds);
        $user->touch();
        (new TokenCacheRepository())->setUserToken($userId, 'REVOKED');
        return ['success' => true, 'action' => 'assign_roles', 'user_id' => $userId, 'role_ids' => $roleIds];
    }

    protected function syncRoles(int $userId, array $roleIds): void
    {
        $validIds = array_filter(array_map('intval', $roleIds), fn($id) => $id > 0);
        if (!empty($validIds)) {
            $existCount = Db::table('roles')->whereIn('id', $validIds)->where('status', 1)->count();
            if ($existCount !== count($validIds)) {
                throw new BusinessException('部分角色不存在或已禁用', 40001);
            }
        }
        Db::table('user_role')->where('user_id', $userId)->delete();
        $rows = [];
        foreach ($validIds as $roleId) {
            $rows[] = ['user_id' => $userId, 'role_id' => $roleId];
        }
        if ($rows) {
            Db::table('user_role')->insert($rows);
        }
    }

    protected function getRoleMap(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }
        $rows = Db::table('user_role')
            ->join('roles', 'roles.id', '=', 'user_role.role_id')
            ->whereIn('user_role.user_id', $userIds)
            ->select('user_role.user_id', 'roles.id as role_id', 'roles.name', 'roles.code')
            ->get()
            ->toArray();
        $map = [];
        foreach ($rows as $r) {
            $r = (array) $r;
            $map[(int) $r['user_id']][] = [
                'id' => (int) $r['role_id'],
                'name' => $r['name'],
                'code' => $r['code'],
            ];
        }
        return $map;
    }
}
