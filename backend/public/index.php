<?php

declare(strict_types=1);

/**
 * public/index.php
 *
 * Phase 1 真实接入准备：
 * - 如果 vendor/autoload.php 不存在，给出明确提示
 * - 如果依赖已安装，加载 autoload 与基础 helper
 * - 当前仍返回占位输出，后续由真实 Webman public 入口接管
 */

$autoload = __DIR__ . '/../vendor/autoload.php';

if (!is_file($autoload)) {
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'code' => 503,
        'msg' => 'Backend dependencies not installed. Run scripts/prepare_backend_dependencies.sh first.',
        'data' => [
            'phase' => 'phase-1-runtime-prep',
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require $autoload;
require_once __DIR__ . '/../support/helpers.php';

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'code' => 1,
    'msg' => 'public entry placeholder ready',
    'data' => [
        'app' => env('APP_NAME', 'webman-search-platform'),
        'env' => env('APP_ENV', 'dev'),
        'phase' => 'phase-1-runtime-prep',
    ],
], JSON_UNESCAPED_UNICODE);
