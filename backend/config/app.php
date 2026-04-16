<?php

/**
 * config/app.php
 *
 * 按官方 Webman 写法收口。
 */

use support\Request;

return [
    'debug' => (bool) env('APP_DEBUG', true),
    'error_reporting' => E_ALL,
    'default_timezone' => 'Asia/Shanghai',
    'request_class' => Request::class,
    'public_path' => base_path() . DIRECTORY_SEPARATOR . 'public',
    'runtime_path' => base_path(false) . DIRECTORY_SEPARATOR . 'runtime',
    'controller_suffix' => 'Controller',
    'controller_reuse' => false,
];
