<?php

$env = static function (string $key, mixed $default = null): mixed {
    if (function_exists('env')) {
        return env($key, $default);
    }
    $value = getenv($key);
    return $value !== false ? $value : $default;
};

return [
    'auth_rbac_source' => $env('AUTH_RBAC_SOURCE', 'real'),
    'question_source' => $env('QUESTION_SOURCE', 'real'),
    'log_source' => $env('LOG_SOURCE', 'real'),
    'user_center_source' => $env('USER_CENTER_SOURCE', 'real'),
    'docs_source' => $env('DOCS_SOURCE', 'real'),
    'collect_source' => $env('COLLECT_SOURCE', 'real'),
    'config_source' => $env('CONFIG_SOURCE', 'real'),
    'api_source_source' => $env('API_SOURCE_SOURCE', 'real'),
];
