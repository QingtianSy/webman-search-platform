<?php

namespace app\service\admin;

use app\exception\BusinessException;
use app\model\admin\User;
use app\repository\redis\TokenCacheRepository;
use app\service\auth\JwtService;
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
        $existing = User::query()->where('username', $data['username'])->exists();
        if ($existing) {
            throw new BusinessException('用户名已存在', 40001);
        }
        return Db::transaction(function () use ($data) {
            $row = new User();
            $row->username = $data['username'];
            $row->password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $row->nickname = $data['nickname'] ?? '';
            $row->mobile = $data['mobile'] ?? '';
            $row->email = $data['email'] ?? '';
            $row->status = $data['status'] ?? 1;
            $row->save();

            $this->provisionDefaults((int) $row->id, $data['role_ids'] ?? null);

            return ['success' => true, 'action' => 'create', 'id' => $row->id, 'data' => $row->makeHidden(['password', 'password_hash'])->toArray()];
        });
    }

    public function update(int $id, array $data): array
    {
        $row = User::query()->find($id);
        if (!$row) {
            throw new BusinessException('用户不存在', 40001);
        }

        return Db::transaction(function () use ($row, $id, $data) {
            if (!empty($data['username']) && $data['username'] !== $row->username) {
                if (User::query()->where('username', $data['username'])->where('id', '!=', $id)->exists()) {
                    throw new BusinessException('用户名已存在', 40001);
                }
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
                $this->revokeToken($id);
            }

            return ['success' => true, 'action' => 'update', 'id' => $id, 'data' => $row->makeHidden(['password', 'password_hash'])->toArray()];
        });
    }

    public function delete(int $id): array
    {
        $row = User::query()->find($id);
        if (!$row) {
            throw new BusinessException('用户不存在', 40001);
        }
        $this->revokeToken($id);
        return Db::transaction(function () use ($row, $id) {
            $row->delete();
            Db::table('user_role')->where('user_id', $id)->delete();
            Db::table('user_api_keys')->where('user_id', $id)->delete();
            Db::table('wallets')->where('user_id', $id)->delete();
            Db::table('user_subscriptions')->where('user_id', $id)->delete();
            // 清理带用户敏感凭据的孤儿行，否则会留下 collect_accounts.cookie_text/token_text
            // 与 user_api_sources.headers/extra_config 等明文凭据（schema 见 0006/0008 迁移）。
            Db::table('collect_accounts')->where('user_id', $id)->delete();
            Db::table('user_api_sources')->where('user_id', $id)->delete();
            return ['success' => true, 'action' => 'delete', 'id' => $id];
        });
    }

    public function toggleStatus(int $id): array
    {
        $row = User::query()->find($id);
        if (!$row) {
            throw new BusinessException('用户不存在', 40001);
        }

        return Db::transaction(function () use ($row, $id) {
            $row->status = $row->status == 1 ? 0 : 1;
            $row->save();

            if ((int) $row->status === 0) {
                $this->revokeToken($id);
                Db::table('user_api_keys')->where('user_id', $id)->update(['status' => 0]);
            }

            return ['success' => true, 'action' => 'toggle_status', 'id' => $id, 'status' => $row->status];
        });
    }

    public function assignRoles(int $userId, array $roleIds): array
    {
        $user = User::query()->find($userId);
        if (!$user) {
            throw new BusinessException('用户不存在', 40001);
        }
        return Db::transaction(function () use ($user, $userId, $roleIds) {
            $this->syncRoles($userId, $roleIds);
            $this->revokeToken($userId);
            return ['success' => true, 'action' => 'assign_roles', 'user_id' => $userId, 'role_ids' => $roleIds];
        });
    }

    // 吊销用户所有已签发 token。
    // 1) DB 写 sessions_invalidated_at(DATETIME(3)) —— 这是权威失效源；用户资料更新（昵称/头像等）不写此列。
    // 2) Redis 尽力 REVOKED/del 做快速路径；Redis 不可用不阻断，中间件会用 DB 兜底。
    // 调用方在事务内外皆可：事务内调用时 DB 写随外层 commit 一起落盘，中间件在 commit 后才能看到。
    protected function revokeToken(int $userId): void
    {
        $now = JwtService::nowDatetime3();
        $updated = Db::table('users')->where('id', $userId)->update(['sessions_invalidated_at' => $now]);
        if ($updated === 0) {
            // 行不存在（已删除）则跳过；仍尝试清理 Redis。
            error_log("[UserAdminService] revokeToken: user={$userId} not found, skipping DB invalidation");
        }

        $tokenRepo = new TokenCacheRepository();
        if ($tokenRepo->setUserToken($userId, 'REVOKED')) {
            return;
        }
        if ($tokenRepo->deleteToken($userId)) {
            return;
        }
        error_log("[UserAdminService] revokeToken cache invalidation skipped for user={$userId}; relying on DB sessions_invalidated_at fallback");
    }

    // 与 AuthService::register 保持同步：新建用户必须初始化钱包与默认角色。
    protected function provisionDefaults(int $userId, ?array $roleIds): void
    {
        if (!Db::table('wallets')->where('user_id', $userId)->exists()) {
            Db::table('wallets')->insert([
                'user_id' => $userId,
                'balance' => 0,
                'frozen_balance' => 0,
                'total_recharge' => 0,
                'total_consume' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if (is_array($roleIds)) {
            $this->syncRoles($userId, $roleIds);
            return;
        }

        $userRole = Db::table('roles')->where('code', 'user')->first();
        if ($userRole) {
            Db::table('user_role')->insert([
                'user_id' => $userId,
                'role_id' => $userRole->id,
            ]);
        }
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
