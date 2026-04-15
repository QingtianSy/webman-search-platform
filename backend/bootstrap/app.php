<?php

return [
    'name' => env('APP_NAME', 'webman-search-platform'),
    'env' => env('APP_ENV', 'dev'),
    'debug' => (bool) env('APP_DEBUG', true),
    'timezone' => 'Asia/Shanghai',
    'route_file' => __DIR__ . '/../config/routes.php',
    'middleware_file' => __DIR__ . '/../config/middleware.php',
];
