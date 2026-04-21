<?php

namespace app\service\auth;

use app\exception\BusinessException;
use app\model\admin\User;
use app\repository\mysql\MenuRepository;
use app\repository\mysql\RolePermissionRepository;
use app\repository\mysql\RoleRepository;
use app\repository\mysql\UserRepository;
use app\repository\mysql\UserRoleRepository;
use app\repository\redis\TokenCacheRepository;
use app\repository\redis\UserAuthCacheRepository;
use support\Db;
use support\ResponseCode;

class AuthService
{
    // 鉴权/RBAC 链路严格化背景：
    //   旧实现 UserRepository/UserRoleRepository/RolePermissionRepository/MenuRepository
    //   在 DB 故障时都静默返 null/[]，最终经由 AuthController 翻译为:
    //     login()        → 40002 "账号或密码错误"（误导用户反复改密）
    //     profile()      → 40002 "未登录或用户不存在"
    //     menus/permissions → 200 + []（前端以为用户刚好没权限）
    //   改为 DB/RBAC 故障统一抛 BusinessException(50001)，由前端显示"服务暂不可用"。
    //   真实业务状态（用户名不存在 / 密码错 / 无权限 / 无菜单）仍返回 []/null 让上层正常翻译为 40002/40003/空菜单。

    public function login(string $username, string $password): array
    {
        if ($username === '' || $password === '') {
            return [];
        }
        try {
            $user = (new UserRepository())->findByUsernameStrict($username);
        } catch (\RuntimeException $e) {
            throw new BusinessException('登录服务暂不可用，请稍后重试', 50001);
        }
        if (!$user) {
            return [];
        }

        $passwordService = new PasswordService();
        if (!$passwordService->verify($password, $user) || (int) ($user['status'] ?? 0) !== 1) {
            return [];
        }

        return $this->buildAuthPayload($user);
    }

    public function profile(int $userId): array
    {
        try {
            $user = (new UserRepository())->findByIdStrict($userId);
        } catch (\RuntimeException $e) {
            throw new BusinessException('用户信息服务暂不可用，请稍后重试', 50001);
        }
        if (!$user) {
            return [];
        }
        return $this->buildAuthPayload($user);
    }

    protected function buildAuthPayload(array $user): array
    {
        $userId = (int) ($user['id'] ?? 0);
        try {
            $roleIds = (new UserRoleRepository())->roleIdsByUserIdStrict($userId);
            $roles = (new RoleRepository())->findByIdsStrict($roleIds);
            $permissions = (new RolePermissionRepository())->permissionCodesByRoleIdsStrict($roleIds);
            $menus = (new MenuRepository())->findByPermissionCodesStrict($permissions);
        } catch (\RuntimeException $e) {
            // 任何 RBAC 仓库故障都抛 50001，避免"DB 挂了 → 这个用户看起来没权限/没菜单"。
            throw new BusinessException('权限服务暂不可用，请稍后重试', 50001);
        }

        unset($user['password'], $user['password_hash'], $user['type']);

        $roleCodes = array_values(array_map(fn ($row) => (string) ($row['code'] ?? ''), $roles));
        $defaultPortal = in_array('admin.access', $permissions, true) ? 'admin' : 'portal';

        return [
            'user' => $user,
            'roles' => $roleCodes,
            'permissions' => $permissions,
            'menus' => $menus,
            'default_portal' => $defaultPortal,
        ];
    }

    public function userLogin(string $username, string $password): array
    {
        return $this->login($username, $password);
    }

    public function adminLogin(string $username, string $password): array
    {
        $payload = $this->login($username, $password);
        if (!$payload || !in_array('admin.access', $payload['permissions'] ?? [], true)) {
            return [];
        }
        return $payload;
    }

    public function updateProfile(int $userId, array $data): array
    {
        try {
            $updated = (new UserRepository())->updateProfileStrict($userId, $data);
        } catch (\RuntimeException $e) {
            throw new BusinessException('用户信息服务暂不可用，请稍后重试', 50001);
        }
        if (!$updated) {
            // 非 DB 故障：记录不存在或本次没有可更新字段。
            return [];
        }
        try {
            $user = (new UserRepository())->findByIdStrict($userId);
        } catch (\RuntimeException $e) {
            throw new BusinessException('用户信息服务暂不可用，请稍后重试', 50001);
        }
        if (!$user) {
            return [];
        }
        unset($user['password'], $user['password_hash'], $user['type']);
        return $user;
    }

    public function register(array $data): array
    {
        $username = $data['username'];
        try {
            $existing = (new UserRepository())->findByUsernameStrict($username);
        } catch (\RuntimeException $e) {
            throw new BusinessException('注册服务暂不可用，请稍后重试', 50001);
        }
        if ($existing) {
            throw new BusinessException('用户名已存在', ResponseCode::PARAM_ERROR);
        }

        // 把 users/wallets/user_role + sessions_invalidated_at bump 放进同一个事务，
        // 任一失败整体回滚，不会出现"账号已建、token 签发失败"的半成品。
        // Redis 缓存放到事务提交之后且容忍失败：DB sessions_invalidated_at 是权威失效源，
        // 缓存缺失时中间件会回落到 DB 校验。
        $result = Db::transaction(function () use ($data, $username) {
            $user = new User();
            $user->username = $username;
            $user->password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $user->nickname = $data['nickname'] ?? '';
            $user->status = 1;
            $user->save();

            Db::table('wallets')->insert([
                'user_id' => $user->id,
                'balance' => 0,
                'frozen_balance' => 0,
                'total_recharge' => 0,
                'total_consume' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $userRole = Db::table('roles')->where('code', 'user')->first();
            if ($userRole) {
                Db::table('user_role')->insert([
                    'user_id' => $user->id,
                    'role_id' => $userRole->id,
                ]);
            }

            $iatMs = (int) round(microtime(true) * 1000);
            $updated = Db::table('users')->where('id', $user->id)->update([
                'sessions_invalidated_at' => JwtService::msToDatetime3($iatMs),
            ]);
            if ($updated === 0) {
                // 刚插入的行此处 update 不到 → 底层异常，回滚整个事务
                throw new \RuntimeException('register: failed to bump sessions_invalidated_at for new user ' . $user->id);
            }

            return ['user' => $user, 'iat_ms' => $iatMs];
        });

        $user = $result['user'];
        $iatMs = $result['iat_ms'];
        $payload = $this->buildAuthPayload($user->toArray());

        $token = (new JwtService())->encode([
            'uid' => $user->id,
            'username' => $user->username,
            'roles' => $payload['roles'],
            'default_portal' => $payload['default_portal'] ?? 'user',
        ], $iatMs);

        // Redis best-effort：写失败（无论连着还是宕机）都不抛错，账号已落库。
        $cache = new TokenCacheRepository();
        if (!$cache->setUserToken((int) $user->id, $token)) {
            error_log("[AuthService] register setUserToken failed for user {$user->id} — token issued, DB bump committed, cache miss will fall back to DB");
        }

        $payload['token'] = $token;
        return $payload;
    }

    // 原子会话签发：先写 Redis（若连着失败→失败）→ 再 bump DB（失败→回滚 Redis）→ 最后返回 token。
    // 任一步骤失败都抛 BusinessException，不留"token 已签发但缓存/DB 未同步"的中间态。
    // 中间态会导致两类问题：
    //   (1) 只 bump 成功：客户端收到 500，但此用户其它活跃会话已被 DB 作废；
    //   (2) 只 setUserToken 成功：DB 未 bump，旧 token 继续有效，新 token 被 Redis 占位但 bump 未完成。
    public function issueSessionToken(int $userId, array $jwtPayload): string
    {
        $iatMs = (int) round(microtime(true) * 1000);
        $token = (new JwtService())->encode($jwtPayload, $iatMs);

        $cache = new TokenCacheRepository();
        $stored = $cache->setUserToken($userId, $token);
        $redisFailedButConnected = false;
        if (!$stored) {
            $status = $cache->getUserTokenWithStatus($userId);
            if ($status['connected']) {
                // Redis 连着但写失败 → 直接放弃签发，不 bump DB，保持零副作用
                throw new BusinessException('会话签发失败，请稍后重试', 50001);
            }
            // Redis 不可用 → fail-open：DB 作为权威仍可工作，仅记录
            $redisFailedButConnected = false;
            error_log("[AuthService] setUserToken failed for user {$userId}, Redis unavailable — token issued without cache");
        }

        try {
            (new UserRepository())->bumpSessionInvalidatedAt($userId, JwtService::msToDatetime3($iatMs));
        } catch (\Throwable $e) {
            // DB 失败：Redis 已持新 token 但 sessions_invalidated_at 未推进，旧 token 仍有效，属于不一致。
            // 回滚 Redis（best-effort）并抛错。
            if ($stored) {
                $cache->deleteToken($userId);
            }
            error_log("[AuthService] issueSessionToken bump failed for user {$userId}: " . $e->getMessage());
            throw new BusinessException('会话签发失败，请稍后重试', 50001);
        }

        // 新 token 签发 = sessions_invalidated_at 已推进；必须把合并鉴权缓存打掉，
        // 否则缓存中旧 invalidated_ms 让旧 token 继续被中间件放行直到 TTL 过期。
        // bust 失败（Redis 连着但 del 抛异常）→ 回滚 Redis token 并 50001 让客户端重试，
        // 重试会生成更晚的 iat_ms，DB GREATEST 幂等，不留双写中间态。
        try {
            (new UserAuthCacheRepository())->bustStrict($userId);
        } catch (\Throwable $e) {
            if ($stored) {
                $cache->deleteToken($userId);
            }
            error_log("[AuthService] issueSessionToken auth cache bust failed for user {$userId}: " . $e->getMessage());
            throw new BusinessException('会话签发失败，请稍后重试', 50001);
        }

        return $token;
    }

    // 登出：权威失效源是 users.sessions_invalidated_at。把它 bump 到当前毫秒，
    // 任何 iat_ms < now 的 token（包括本次请求自己携带的）都会在下一次中间件校验时被拒。
    // bumpSessionInvalidatedAt 用 GREATEST + 存在性回退，同毫秒重试 / 网关重发都是幂等成功。
    // bust 失败（Redis 连着但 del 抛异常）→ 50001：DB 已生效，但 auth cache 仍有旧 invalidated_ms，
    // 不抛错就会让已登出 token 继续放行 ≤60s。重试是幂等的（DB GREATEST + bust 再清一次）。
    public function logout(int $userId): void
    {
        $nowMs = (int) round(microtime(true) * 1000);
        try {
            (new UserRepository())->bumpSessionInvalidatedAt($userId, JwtService::msToDatetime3($nowMs));
        } catch (\Throwable $e) {
            error_log("[AuthService] logout bump failed for user {$userId}: " . $e->getMessage());
            throw new BusinessException('登出失败，请稍后重试', 50001);
        }

        // token 缓存清理失败影响小：中间件不再以 token key 缺失=吊销，best-effort 即可。
        (new TokenCacheRepository())->deleteToken($userId);

        try {
            (new UserAuthCacheRepository())->bustStrict($userId);
        } catch (\Throwable $e) {
            error_log("[AuthService] logout auth cache bust failed for user {$userId}: " . $e->getMessage());
            throw new BusinessException('登出失败，请稍后重试', 50001);
        }
    }
}
