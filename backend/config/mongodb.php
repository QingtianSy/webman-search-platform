<?php

$env = static function (string $key, mixed $default = null): mixed {
    if (function_exists('env')) {
        return env($key, $default);
    }
    $value = getenv($key);
    return $value !== false ? $value : $default;
};

return [
    'uri' => $env('MONGO_URI', 'mongodb://127.0.0.1:27017'),
    'database' => $env('MONGO_DATABASE', 'search_platform'),
];
