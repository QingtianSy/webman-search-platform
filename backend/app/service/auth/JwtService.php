<?php

namespace app\service\auth;

class JwtService
{
    protected function getSecret(): string
    {
        $secret = config('jwt.secret', '');
        if ($secret === '' || $secret === 'please_change_me') {
            throw new \RuntimeException('JWT secret is not configured. Set JWT_SECRET in .env before using authentication.');
        }
        return $secret;
    }

    protected function sign(string $data): string
    {
        return hash_hmac('sha256', $data, $this->getSecret());
    }

    public function encode(array $payload, ?int $iatMs = null): string
    {
        $jwtConfig = config('jwt', []);
        $now = time();
        // iat_ms 用于与 users.sessions_invalidated_at(DATETIME(3)) 做毫秒级比较，
        // 消除"密码变更与登录在同一秒"时秒级 iat 的绕过窗口。iat 保留向后兼容。
        // 登录/注册链路会外部传入 $iatMs 并把同值写入 sessions_invalidated_at，
        // 让"本次登录之前签发的所有 token"（iat < invalidated）在中间件处被拦下。
        $iatMs = $iatMs ?? (int) round(microtime(true) * 1000);
        $data = [
            'payload' => $payload,
            'iat' => $now,
            'iat_ms' => $iatMs,
            'exp' => $now + (int) ($jwtConfig['expire'] ?? 604800),
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

    // 把 MySQL DATETIME(3) 字符串转换为毫秒时间戳，用于与 iat_ms 对比。
    // strtotime 会丢掉小数秒，因此手工解析 ".vvv" 部分。
    public static function datetimeToMs(?string $dt): int
    {
        if ($dt === null || $dt === '') {
            return 0;
        }
        $parts = explode('.', $dt, 2);
        $sec = strtotime($parts[0]);
        if ($sec === false) {
            return 0;
        }
        $ms = 0;
        if (isset($parts[1]) && $parts[1] !== '') {
            $frac = str_pad(substr($parts[1], 0, 3), 3, '0', STR_PAD_RIGHT);
            $ms = (int) $frac;
        }
        return $sec * 1000 + $ms;
    }

    // 生成 MySQL DATETIME(3) 字符串写入 sessions_invalidated_at。
    public static function nowDatetime3(): string
    {
        $mt = microtime(true);
        $sec = (int) $mt;
        $ms = (int) round(($mt - $sec) * 1000);
        if ($ms >= 1000) {
            $sec += 1;
            $ms -= 1000;
        }
        return date('Y-m-d H:i:s', $sec) . '.' . str_pad((string) $ms, 3, '0', STR_PAD_LEFT);
    }

    // 把毫秒时间戳转为 MySQL DATETIME(3) 字符串；用于登录时让 sessions_invalidated_at 与新 token 的 iat_ms 严格同值。
    public static function msToDatetime3(int $ms): string
    {
        $sec = intdiv($ms, 1000);
        $frac = $ms % 1000;
        return date('Y-m-d H:i:s', $sec) . '.' . str_pad((string) $frac, 3, '0', STR_PAD_LEFT);
    }
}
