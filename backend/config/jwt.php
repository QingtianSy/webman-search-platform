<?php

$env = static function (string $key, mixed $default = null): mixed {
    if (function_exists('env')) {
        return env($key, $default);
    }
    $value = getenv($key);
    return $value !== false ? $value : $default;
};

return [
    'secret' => $env('JWT_SECRET', 'please_change_me'),
    'expire' => (int) $env('JWT_EXPIRE', 604800),
    'issuer' => $env('APP_NAME', 'webman-search-platform'),
];
