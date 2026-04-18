<?php

$env = static function (string $key, mixed $default = null): mixed {
    if (function_exists('env')) {
        return env($key, $default);
    }
    $value = getenv($key);
    return $value !== false ? $value : $default;
};

return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => $env('MYSQL_HOST', '127.0.0.1'),
            'port' => (int) $env('MYSQL_PORT', 3306),
            'database' => $env('MYSQL_DATABASE', 'search_platform'),
            'username' => $env('MYSQL_USERNAME', 'root'),
            'password' => $env('MYSQL_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ],
    ],
];
