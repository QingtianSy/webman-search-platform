<?php

/**
 * config/middleware.php
 *
 * 更贴官方 Webman 的全局中间件注册方式。
 */

return [
    '' => [
        app\middleware\StaticFile::class,
        app\middleware\RequestIdMiddleware::class,
    ],
];
