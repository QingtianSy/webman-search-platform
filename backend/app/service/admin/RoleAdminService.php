<?php

namespace app\service\admin;

use app\exception\BusinessException;
use app\model\admin\Role;
use app\repository\mysql\UserRoleRepository;
use app\repository\redis\PermissionCacheRepository;
use app\repository\redis\TokenCacheRepository;
use app\service\auth\JwtService;
use support\Db;
use support\Pagination;

class RoleAdminService
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
        $sortable = ['id', 'name', 'code', 'sort', 'status', 'created_at', 'updated_at'];
        if (!in_array($sort, $sortable, true)) {
            $sort = 'id';
        }
        $builder = Role::query();
        if ($keyword !== '') {
            $builder->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('code', 'like', "%{$keyword}%");
            });
        }
        if ($status !== null && $status !== '' && $status !== 'all') {
            $builder->where('status', $status);
        }
        $total = $builder->count();
        $list = $builder->orderBy($sort, $order)->forPage($page, $pageSize)->get()->toArray();

        $roleIds = array_column($list, 'id');
        $permMap = $this->getPermissionMap($roleIds);
        foreach ($list as &$row) {
            $row['permissions'] = $permMap[(int) $row['id']] ?? [];
        }
        return Pagination::format($list, $total, $page, $pageSize);
    }

    public function create(array $data): array
    {
        if (Role::query()->where('code', $data['code'] ?? '')->exists()) {
            throw new BusinessException('角色编码已存在', 40001);
        }
        $row = new Role();
        $row->fill($data);
        $row->save();
        return ['success' => true, 'action' => 'create', 'id' => $row->id, 'data' => $row->toArray()];
    }

    public function update(int $id, array $data): array
    {
        $row = Role::query()->find($id);
        if (!$row) {
            throw new BusinessException('角色不存在', 40001);
        }
        $permissionIds = $data['permission_ids'] ?? null;
        unset($data['permission_ids'], $data['id']);
        $result = Db::transaction(function () use ($row, $id, $data, $permissionIds) {
            if (!empty($data['code']) && $data['code'] !== $row->code) {
                if (Role::query()->where('code', $data['code'])->where('id', '!=', $id)->exists()) {
                    throw new BusinessException('角色编码已存在', 40001);
                }
            }
            $affectedUserIds = (new UserRoleRepository())->userIdsByRoleId($id);
            $row->fill($data);
            $row->save();
            if (is_array($permissionIds)) {
                $this->syncPermissions($id, $permissionIds);
            }
            return [
                'response' => ['success' => true, 'action' => 'update', 'id' => $id, 'data' => $row->toArray()],
                'affected_user_ids' => $affectedUserIds,
            ];
        });
        // 事务提交后再清缓存 / 吊销 token，避免事务中途清掉缓存后，
        // 并发读请求用事务外的旧 DB 状态把缓存重新填成旧数据。
        (new PermissionCacheRepository())->clearAll();
        $this->revokeUserTokens($result['affected_user_ids']);
        return $result['response'];
    }

    public function delete(int $id): array
    {
        $row = Role::query()->find($id);
        if (!$row) {
            throw new BusinessException('角色不存在', 40001);
        }
        $affectedUserIds = Db::transaction(function () use ($row, $id) {
            $ids = (new UserRoleRepository())->userIdsByRoleId($id);
            $row->delete();
            Db::table('role_permission')->where('role_id', $id)->delete();
            Db::table('user_role')->where('role_id', $id)->delete();
            return $ids;
        });
        (new PermissionCacheRepository())->clearAll();
        $this->revokeUserTokens($affectedUserIds);
        return ['success' => true, 'action' => 'delete', 'id' => $id];
    }

    public function assignPermissions(int $roleId, array $permissionIds): array
    {
        if (!Role::query()->where('id', $roleId)->exists()) {
            throw new BusinessException('角色不存在', 40001);
        }
        $affectedUserIds = Db::transaction(function () use ($roleId, $permissionIds) {
            $ids = (new UserRoleRepository())->userIdsByRoleId($roleId);
            $this->syncPermissions($roleId, $permissionIds);
            return $ids;
        });
        (new PermissionCacheRepository())->clearAll();
        $this->revokeUserTokens($affectedUserIds);
        return ['success' => true, 'action' => 'assign_permissions', 'role_id' => $roleId, 'permission_ids' => $permissionIds];
    }

    protected function syncPermissions(int $roleId, array $permissionIds): void
    {
        $validIds = array_filter(array_map('intval', $permissionIds), fn($id) => $id > 0);
        if (!empty($validIds)) {
            $existCount = Db::table('permissions')->whereIn('id', $validIds)->where('status', 1)->count();
            if ($existCount !== count($validIds)) {
                throw new BusinessException('部分权限不存在或已禁用', 40001);
            }
        }
        Db::table('role_permission')->where('role_id', $roleId)->delete();
        $rows = [];
        foreach ($validIds as $pid) {
            $rows[] = ['role_id' => $roleId, 'permission_id' => $pid];
        }
        if ($rows) {
            Db::table('role_permission')->insert($rows);
        }
    }

    protected function getPermissionMap(array $roleIds): array
    {
        if (empty($roleIds)) {
            return [];
        }
        $rows = Db::table('role_permission')
            ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->whereIn('role_permission.role_id', $roleIds)
            ->select('role_permission.role_id', 'permissions.id as permission_id', 'permissions.name', 'permissions.code')
            ->get()
            ->toArray();
        $map = [];
        foreach ($rows as $r) {
            $r = (array) $r;
            $map[(int) $r['role_id']][] = [
                'id' => (int) $r['permission_id'],
                'name' => $r['name'],
                'code' => $r['code'],
            ];
        }
        return $map;
    }

    protected function revokeUserTokens(array $userIds): void
    {
        if (empty($userIds)) {
            return;
        }
        // 写 sessions_invalidated_at 作为权威失效源；Redis 仅作为快速路径，不可用时中间件用 DB 兜底。
        Db::table('users')->whereIn('id', $userIds)->update(['sessions_invalidated_at' => JwtService::nowDatetime3()]);
        $tokenRepo = new TokenCacheRepository();
        foreach ($userIds as $uid) {
            if (!$tokenRepo->setUserToken($uid, 'REVOKED')) {
                $tokenRepo->deleteToken($uid);
            }
        }
    }
}
