<?php

namespace app\service\auth;

class JwtService
{
    public function encode(array $payload): string
    {
        return 'todo_jwt_token';
    }

    public function decode(string $token): array
    {
        return [];
    }
}
