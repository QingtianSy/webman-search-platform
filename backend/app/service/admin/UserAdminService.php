<?php

namespace app\service\admin;

use app\exception\BusinessException;
use app\model\admin\User;
use app\repository\mysql\BalanceLogRepository;
use app\repository\redis\QuotaCacheRepository;
use app\repository\redis\TokenCacheRepository;
use app\repository\redis\UserAuthCacheRepository;
use app\service\auth\JwtService;
use support\Db;
use support\Pagination;

class UserAdminService
{
    public function getList(array $query = []): array
    {
        $query += [
            'keyword' => '',
            'status' => null,
            'page' => 1,
            'page_size' => 20,
            'sort' => '',
            'order' => 'desc',
        ];
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
        $walletMap = $this->getWalletMap($userIds);
        $subMap = $this->getSubscriptionMap($userIds);

        foreach ($list as &$row) {
            unset($row['type']);
            $row['roles'] = $roleMap[(int) $row['id']] ?? [];
            $row['balance'] = $walletMap[(int) $row['id']] ?? '0.00';
            $sub = $subMap[(int) $row['id']] ?? null;
            $row['subscription_name'] = $sub['name'] ?? null;
            $row['subscription_expire_at'] = $sub['expire_at'] ?? null;
            $row['subscription_is_unlimited'] = $sub ? (int) $sub['is_unlimited'] : null;
            $row['subscription_remain_quota'] = $sub ? (int) $sub['remain_quota'] : null;
        }

        return Pagination::format($list, $total, $page, $pageSize);
    }

    public function create(array $data): array
    {
        if (User::query()->where('username', $data['username'])->exists()) {
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

            return [
                'success' => true,
                'action' => 'create',
                'id' => $row->id,
                'data' => $row->makeHidden(['password', 'password_hash'])->toArray(),
            ];
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

            $balanceResult = null;
            if (array_key_exists('balance_delta', $data) && (float) $data['balance_delta'] != 0) {
                $balanceResult = $this->applyBalanceChange($id, (float) $data['balance_delta'], (string) ($data['balance_remark'] ?? ''));
            }

            $subscriptionResult = null;
            if (array_key_exists('plan_id', $data)) {
                $durationDays = $data['plan_duration_days'] ?? null;
                $subscriptionResult = $this->applySubscriptionChange(
                    $id,
                    $data['plan_id'] === null ? null : (int) $data['plan_id'],
                    $durationDays === null ? null : (int) $durationDays
                );
            }

            $shouldRevokeToken =
                !empty($data['password'])
                || (isset($data['status']) && (int) $data['status'] === 0)
                || isset($data['role_ids']);

            if ($shouldRevokeToken) {
                $this->revokeToken($id);
            } elseif (
                isset($data['status'])
                || isset($data['nickname'])
                || isset($data['mobile'])
                || isset($data['email'])
                || !empty($data['username'])
            ) {
                (new UserAuthCacheRepository())->bust($id);
            }

            return [
                'success' => true,
                'action' => 'update',
                'id' => $id,
                'data' => $row->makeHidden(['password', 'password_hash'])->toArray(),
                'balance' => $balanceResult['balance'] ?? null,
                'subscription' => $subscriptionResult,
            ];
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
            } else {
                (new UserAuthCacheRepository())->bust($id);
            }

            return [
                'success' => true,
                'action' => 'toggle_status',
                'id' => $id,
                'status' => $row->status,
            ];
        });
    }

    public function assignRoles(int $userId, array $roleIds): array
    {
        $user = User::query()->find($userId);
        if (!$user) {
            throw new BusinessException('用户不存在', 40001);
        }

        return Db::transaction(function () use ($userId, $roleIds) {
            $this->syncRoles($userId, $roleIds);
            $this->revokeToken($userId);
            return [
                'success' => true,
                'action' => 'assign_roles',
                'user_id' => $userId,
                'role_ids' => $roleIds,
            ];
        });
    }

    protected function revokeToken(int $userId): void
    {
        $now = JwtService::nowDatetime3();
        $updated = Db::table('users')->where('id', $userId)->update(['sessions_invalidated_at' => $now]);
        if ($updated === 0) {
            error_log("[UserAdminService] revokeToken: user={$userId} not found, skipping DB invalidation");
        }

        (new UserAuthCacheRepository())->bust($userId);

        $tokenRepo = new TokenCacheRepository();
        if ($tokenRepo->setUserToken($userId, 'REVOKED')) {
            return;
        }
        if ($tokenRepo->deleteToken($userId)) {
            return;
        }
        error_log("[UserAdminService] revokeToken cache invalidation skipped for user={$userId}; relying on DB fallback");
    }

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
        $validIds = array_values(array_unique(array_filter(array_map('intval', $roleIds), fn($roleId) => $roleId > 0)));
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
        foreach ($rows as $row) {
            $row = (array) $row;
            $map[(int) $row['user_id']][] = [
                'id' => (int) $row['role_id'],
                'name' => $row['name'],
                'code' => $row['code'],
            ];
        }
        return $map;
    }

    protected function getWalletMap(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }
        $rows = Db::table('wallets')
            ->whereIn('user_id', $userIds)
            ->select('user_id', 'balance')
            ->get()
            ->toArray();

        $map = [];
        foreach ($rows as $row) {
            $row = (array) $row;
            $map[(int) $row['user_id']] = $row['balance'];
        }
        return $map;
    }

    protected function getSubscriptionMap(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }
        $rows = Db::table('user_subscriptions')
            ->whereIn('user_id', $userIds)
            ->where(function ($q) {
                $q->whereNull('expire_at')->orWhere('expire_at', '>', date('Y-m-d H:i:s'));
            })
            ->select('user_id', 'name', 'is_unlimited', 'remain_quota', 'expire_at')
            ->orderBy('id', 'desc')
            ->get()
            ->toArray();

        $map = [];
        foreach ($rows as $row) {
            $row = (array) $row;
            $userId = (int) $row['user_id'];
            if (!isset($map[$userId])) {
                $map[$userId] = $row;
            }
        }
        return $map;
    }

    public function adjustBalance(int $id, float $amount, string $remark): array
    {
        if (!User::query()->find($id)) {
            throw new BusinessException('用户不存在', 40001);
        }
        if ($amount == 0) {
            throw new BusinessException('调整金额不能为 0', 40001);
        }

        return Db::transaction(fn() => $this->applyBalanceChange($id, $amount, $remark));
    }

    public function setSubscription(int $id, ?int $planId, ?int $durationDays = null): array
    {
        if (!User::query()->find($id)) {
            throw new BusinessException('用户不存在', 40001);
        }

        return Db::transaction(fn() => $this->applySubscriptionChange($id, $planId, $durationDays));
    }

    public function resetPassword(int $id, string $newPassword): array
    {
        $row = User::query()->find($id);
        if (!$row) {
            throw new BusinessException('用户不存在', 40001);
        }
        $row->password_hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $row->save();
        $this->revokeToken($id);
        return ['success' => true, 'action' => 'reset_password', 'id' => $id];
    }

    public function forceOffline(int $id): array
    {
        if (!User::query()->find($id)) {
            throw new BusinessException('用户不存在', 40001);
        }
        $this->revokeToken($id);
        return ['success' => true, 'action' => 'force_offline', 'id' => $id];
    }

    protected function applyBalanceChange(int $id, float $amount, string $remark): array
    {
        if ($amount == 0) {
            throw new BusinessException('调整金额不能为 0', 40001);
        }
        if ($remark === '') {
            throw new BusinessException('调整备注不能为空', 40001);
        }

        $wallet = Db::table('wallets')->where('user_id', $id)->lockForUpdate()->first();
        if (!$wallet) {
            Db::table('wallets')->insert([
                'user_id' => $id,
                'balance' => 0,
                'frozen_balance' => 0,
                'total_recharge' => 0,
                'total_consume' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $wallet = Db::table('wallets')->where('user_id', $id)->lockForUpdate()->first();
        }

        $wallet = (array) $wallet;
        $oldBalance = (float) $wallet['balance'];
        $newBalance = round($oldBalance + $amount, 2);
        if ($newBalance < 0) {
            throw new BusinessException('余额不足，扣减后为负', 40001);
        }

        $updates = [
            'balance' => $newBalance,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($amount > 0) {
            $updates['total_recharge'] = Db::raw("total_recharge + {$amount}");
        } else {
            $absAmount = abs($amount);
            $updates['total_consume'] = Db::raw("total_consume + {$absAmount}");
        }
        Db::table('wallets')->where('user_id', $id)->update($updates);

        (new BalanceLogRepository())->create([
            'user_id' => $id,
            'type' => $amount > 0 ? 'admin_recharge' : 'admin_deduct',
            'amount' => $amount,
            'balance_after' => $newBalance,
            'remark' => $remark,
        ]);

        return [
            'success' => true,
            'action' => 'adjust_balance',
            'id' => $id,
            'balance' => number_format($newBalance, 2, '.', ''),
        ];
    }

    protected function applySubscriptionChange(int $id, ?int $planId, ?int $durationDays = null): array
    {
        if ($planId === null || $planId === 0) {
            Db::table('user_subscriptions')->where('user_id', $id)->delete();
            (new QuotaCacheRepository())->deleteUserQuota($id);
            return [
                'success' => true,
                'action' => 'clear_subscription',
                'id' => $id,
            ];
        }

        $plan = Db::table('plans')->where('id', $planId)->where('status', 1)->first();
        if (!$plan) {
            throw new BusinessException('套餐不存在或已下架', 40001);
        }
        $plan = (array) $plan;

        $duration = $durationDays ?? (int) $plan['duration'];
        if ($duration < 0) {
            throw new BusinessException('套餐时长不能小于 0', 40001);
        }

        $isUnlimited = (int) $plan['is_unlimited'];
        $quota = (int) $plan['quota'];
        $expireAt = $duration > 0 ? date('Y-m-d H:i:s', strtotime("+{$duration} days")) : null;
        $remainQuota = $isUnlimited ? 0 : $quota;

        Db::table('user_subscriptions')->where('user_id', $id)->delete();
        Db::table('user_subscriptions')->insert([
            'user_id' => $id,
            'name' => $plan['name'],
            'is_unlimited' => $isUnlimited,
            'remain_quota' => $remainQuota,
            'used_quota' => 0,
            'expire_at' => $expireAt,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        (new QuotaCacheRepository())->deleteUserQuota($id);

        return [
            'success' => true,
            'action' => 'set_subscription',
            'id' => $id,
            'plan_name' => $plan['name'],
            'duration_days' => $duration,
            'expire_at' => $expireAt,
        ];
    }
}
