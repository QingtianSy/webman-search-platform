<?php

namespace app\service\auth;

class JwtService
{
    public function encode(array $payload): string
    {
        $data = [
            'payload' => $payload,
            'iat' => time(),
            'exp' => time() + (int) env('JWT_EXPIRE', 604800),
            'iss' => env('APP_NAME', 'webman-search-platform'),
        ];
        return base64_encode(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    public function decode(string $token): array
    {
        $decoded = json_decode(base64_decode($token), true);
        if (!is_array($decoded)) {
            return [];
        }
        if (($decoded['exp'] ?? 0) < time()) {
            return [];
        }
        return $decoded;
    }
}
