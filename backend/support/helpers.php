<?php

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}

if (!function_exists('base_path')) {
    function base_path(bool $withSuffix = true): string
    {
        return dirname(__DIR__);
    }
}

if (!function_exists('runtime_path')) {
    function runtime_path(string $path = ''): string
    {
        return base_path() . DIRECTORY_SEPARATOR . 'runtime' . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        static $configs = [];
        [$file, $subKey] = array_pad(explode('.', $key, 2), 2, null);
        if (!isset($configs[$file])) {
            $path = __DIR__ . '/../config/' . $file . '.php';
            $configs[$file] = is_file($path) ? require $path : [];
        }
        if ($subKey === null || $subKey === '') {
            return $configs[$file] ?? $default;
        }
        $value = $configs[$file] ?? [];
        foreach (explode('.', $subKey) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}
