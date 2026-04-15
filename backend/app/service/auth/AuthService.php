<?php

namespace app\service\auth;

class AuthService
{
    public function userLogin(string $username, string $password): array
    {
        if ($username === '' || $password === '') {
            return [];
        }
        return [
            'id' => 1,
            'username' => $username,
            'nickname' => '测试用户',
            'avatar' => '',
            'status' => 1,
        ];
    }

    public function adminLogin(string $username, string $password): array
    {
        if ($username === '' || $password === '') {
            return [];
        }
        return [
            'id' => 1,
            'username' => $username,
            'nickname' => '超级管理员',
            'avatar' => '',
            'status' => 1,
        ];
    }
}
