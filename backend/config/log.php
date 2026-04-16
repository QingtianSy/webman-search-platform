<?php

/**
 * config/log.php
 *
 * 后续真实接入时，可将日志统一收口到 runtime/logs。
 */
return [
    'default' => [
        'path' => base_path('runtime/logs/app.log'),
        'level' => 'debug',
    ],
];
