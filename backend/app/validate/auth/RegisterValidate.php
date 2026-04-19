<?php

namespace app\validate\auth;

use app\exception\BusinessException;
use support\ResponseCode;

class RegisterValidate
{
    public function register(array $data): array
    {
        $username = trim((string) ($data['username'] ?? ''));
        if ($username === '' || mb_strlen($username) < 3 || mb_strlen($username) > 50) {
            throw new BusinessException('用户名长度需在3-50个字符之间', ResponseCode::PARAM_ERROR);
        }
        $password = (string) ($data['password'] ?? '');
        if (mb_strlen($password) < 6) {
            throw new BusinessException('密码长度不能小于6位', ResponseCode::PARAM_ERROR);
        }
        return [
            'username' => $username,
            'password' => $password,
            'nickname' => trim((string) ($data['nickname'] ?? '')),
        ];
    }
}
