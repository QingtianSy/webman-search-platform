<?php

namespace app\service\auth;

use app\repository\mysql\UserRepository;

class AuthService
{
    public function userLogin(string $username, string $password): array
    {
        if ($username === '' || $password === '') {
            return [];
        }
        $user = (new UserRepository())->findByUsername($username);
        if (!$user || ($user['type'] ?? '') !== 'user') {
            return [];
        }
        if (($user['password'] ?? '') !== $password || (int) ($user['status'] ?? 0) !== 1) {
            return [];
        }
        unset($user['password']);
        return $user;
    }

    public function adminLogin(string $username, string $password): array
    {
        if ($username === '' || $password === '') {
            return [];
        }
        $user = (new UserRepository())->findByUsername($username);
        if (!$user || ($user['type'] ?? '') !== 'admin') {
            return [];
        }
        if (($user['password'] ?? '') !== $password || (int) ($user['status'] ?? 0) !== 1) {
            return [];
        }
        unset($user['password']);
        return $user;
    }
}
