<?php

$env = static function (string $key, mixed $default = null): mixed {
    if (function_exists('env')) {
        return env($key, $default);
    }
    $value = getenv($key);
    return $value !== false ? $value : $default;
};

return [
    'default' => [
        'host' => $env('REDIS_HOST', '127.0.0.1'),
        'port' => (int) $env('REDIS_PORT', 6379),
        'password' => $env('REDIS_PASSWORD', ''),
        'database' => (int) $env('REDIS_DB', 0),
    ],
];
