<?php

namespace app\service\auth;

class JwtService
{
    public function encode(array $payload): string
    {
        $data = [
            'payload' => $payload,
            'exp' => time() + (int) env('JWT_EXPIRE', 604800),
        ];
        return base64_encode(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    public function decode(string $token): array
    {
        $decoded = json_decode(base64_decode($token), true);
        return is_array($decoded) ? $decoded : [];
    }
}
