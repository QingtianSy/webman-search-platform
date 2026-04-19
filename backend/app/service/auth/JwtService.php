<?php

namespace app\service\auth;

class JwtService
{
    protected static bool $secretWarned = false;

    protected function getSecret(): string
    {
        $secret = config('jwt.secret', 'please_change_me');
        if ($secret === 'please_change_me' && !self::$secretWarned) {
            self::$secretWarned = true;
            error_log('[SECURITY] JWT secret is using default value "please_change_me". Set JWT_SECRET in .env for production.');
        }
        return $secret;
    }

    protected function sign(string $data): string
    {
        return hash_hmac('sha256', $data, $this->getSecret());
    }

    public function encode(array $payload): string
    {
        $jwtConfig = config('jwt', []);
        $data = [
            'payload' => $payload,
            'iat' => time(),
            'exp' => time() + (int) ($jwtConfig['expire'] ?? 604800),
            'iss' => $jwtConfig['issuer'] ?? 'webman-search-platform',
        ];
        $json = base64_encode(json_encode($data, JSON_UNESCAPED_UNICODE));
        $signature = $this->sign($json);
        return $json . '.' . $signature;
    }

    public function decode(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return [];
        }
        [$json, $signature] = $parts;
        if (!hash_equals($this->sign($json), $signature)) {
            return [];
        }
        $decoded = json_decode(base64_decode($json), true);
        if (!is_array($decoded)) {
            return [];
        }
        if (($decoded['exp'] ?? 0) < time()) {
            return [];
        }
        return $decoded;
    }
}
