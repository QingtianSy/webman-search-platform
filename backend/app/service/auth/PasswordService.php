<?php

namespace app\service\auth;

class PasswordService
{
    public function verify(string $plain, array $user): bool
    {
        if (isset($user['password_hash']) && $user['password_hash'] !== '') {
            return password_verify($plain, (string) $user['password_hash']);
        }
        return false;
    }
}
