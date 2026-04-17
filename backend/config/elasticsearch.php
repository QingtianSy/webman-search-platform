<?php

$env = static function (string $key, mixed $default = null): mixed {
    if (function_exists('env')) {
        return env($key, $default);
    }
    $value = getenv($key);
    return $value !== false ? $value : $default;
};

return [
    'host' => $env('ES_HOST', 'http://127.0.0.1:9200'),
    'username' => $env('ES_USERNAME', ''),
    'password' => $env('ES_PASSWORD', ''),
    'index' => [
        'question' => 'question_index',
        'search_log' => 'search_log_index',
        'collect_log' => 'collect_log_index',
    ],
];
