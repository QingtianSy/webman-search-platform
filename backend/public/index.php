<?php
/**
 * public/index.php
 *
 * 当前阶段：
 * - 作为官方 Webman 公开入口的预留锚点
 * - 在未完成真实 Webman 接管前，保持最小提示逻辑
 *
 * 后续阶段：
 * - 由官方 Webman 入口接管
 */

if (!file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    http_response_code(503);
    echo json_encode([
        'code' => 503,
        'msg' => 'backend dependencies not installed',
        'hint' => 'run scripts/prepare_backend_dependencies.sh first'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo 'webman public entry placeholder';
