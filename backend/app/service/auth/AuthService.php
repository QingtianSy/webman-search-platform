<?php

namespace app\service\auth;

use app\repository\mysql\MenuRepository;
use app\repository\mysql\RolePermissionRepository;
use app\repository\mysql\RoleRepository;
use app\repository\mysql\UserRepository;
use app\repository\mysql\UserRoleRepository;

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

        unset($user['password'], $user['password_hash']);
        return [
            'user' => $user,
            'roles' => array_values(array_map(fn ($row) => (string) ($row['code'] ?? ''), $roles)),
            'permissions' => $permissions,
            'menus' => $menus,
            'default_portal' => in_array('admin.access', $permissions, true) ? 'admin' : 'portal',
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
}
