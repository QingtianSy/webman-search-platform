<?php

return [
    'secret' => env('JWT_SECRET', 'please_change_me'),
    'expire' => (int) env('JWT_EXPIRE', 604800),
    'issuer' => env('APP_NAME', 'webman-search-platform'),
];
