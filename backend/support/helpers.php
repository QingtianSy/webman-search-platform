<?php

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}

if (!function_exists('base_path')) {
    function base_path(bool $appendPath = true): string
    {
        return dirname(__DIR__);
    }
}

if (!function_exists('runtime_path')) {
    function runtime_path(): string
    {
        return base_path() . DIRECTORY_SEPARATOR . 'runtime';
    }
}
