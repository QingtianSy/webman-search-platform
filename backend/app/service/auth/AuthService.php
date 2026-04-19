<?php

namespace app\service\auth;

use app\exception\BusinessException;
use app\model\admin\User;
use app\repository\mysql\MenuRepository;
use app\repository\mysql\RolePermissionRepository;
use app\repository\mysql\RoleRepository;
use app\repository\mysql\UserRepository;
use app\repository\mysql\UserRoleRepository;
use support\Db;
use support\ResponseCode;

class AuthService
{
    public function login(string $username, string $password): array
    {
        if ($username === '' || $password === '') {
            return [];
        }
        $user = (new UserRepository())->findByUsername($username);
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
        $user = (new UserRepository())->findById($userId);
        if (!$user) {
            return [];
        }
        return $this->buildAuthPayload($user);
    }

    protected function buildAuthPayload(array $user): array
    {
        $userId = (int) ($user['id'] ?? 0);
        $roleIds = (new UserRoleRepository())->roleIdsByUserId($userId);
        $roles = (new RoleRepository())->findByIds($roleIds);
        $permissions = (new RolePermissionRepository())->permissionCodesByRoleIds($roleIds);
        $menus = array_values(array_filter((new MenuRepository())->all(), function ($row) use ($permissions) {
            return in_array((string) ($row['permission_code'] ?? ''), $permissions, true);
        }));

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
        $updated = (new UserRepository())->updateProfile($userId, $data);
        if (!$updated) {
            return [];
        }
        $user = (new UserRepository())->findById($userId);
        if (!$user) {
            return [];
        }
        unset($user['password'], $user['password_hash'], $user['type']);
        return $user;
    }

    public function register(array $data): array
    {
        $username = $data['username'];
        $existing = (new UserRepository())->findByUsername($username);
        if ($existing) {
            throw new BusinessException('用户名已存在', ResponseCode::PARAM_ERROR);
        }

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

        return $this->buildAuthPayload($user->toArray());
    }
}
