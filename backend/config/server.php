<?php

/**
 * config/server.php
 *
 * 按官方 Webman 写法收口，并明确 Swoole 事件循环配置。
 */

return [
    'event_loop' => \Workerman\Events\Swoole::class,
    'stop_timeout' => 2,
    'pid_file' => runtime_path() . '/webman.pid',
    'status_file' => runtime_path() . '/webman.status',
    'stdout_file' => runtime_path() . '/logs/stdout.log',
    'log_file' => runtime_path() . '/logs/workerman.log',
    'max_package_size' => 10 * 1024 * 1024,
];
