<?php

/**
 * 应用引导占位配置。
 *
 * 当前用途：
 * - 固定项目级配置入口
 * - 为后续真实 Webman 配置接管保留清晰边界
 */
return [
    'name' => env('APP_NAME', 'webman-search-platform'),
    'env' => env('APP_ENV', 'dev'),
    'debug' => (bool) env('APP_DEBUG', true),
    'timezone' => 'Asia/Shanghai',
    'route_file' => __DIR__ . '/../config/routes.php',
    'middleware_file' => __DIR__ . '/../config/middleware.php',
    'integration_mode' => 'placeholder',
];
