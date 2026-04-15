<?php

return [
    'global' => [
        app\middleware\RequestIdMiddleware::class,
    ],
    'user' => [
        app\middleware\UserAuthMiddleware::class,
    ],
    'admin' => [
        app\middleware\AdminAuthMiddleware::class,
    ],
    'open' => [
        app\middleware\OpenApiAuthMiddleware::class,
    ],
];
