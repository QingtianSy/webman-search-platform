<?php

declare(strict_types=1);

require_once __DIR__ . '/support/helpers.php';

/**
 * start.php
 *
 * 当前阶段：
 * - 保留宿主机命令入口习惯：php start.php start
 * - 在官方 Webman 接管前，只承担占位与依赖提示职责
 *
 * 后续阶段：
 * - 替换为官方启动入口逻辑
 */

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    fwrite(STDERR, "backend dependencies not installed, run scripts/prepare_backend_dependencies.sh first\n");
    exit(1);
}

$command = $argv[1] ?? 'status';

echo "webman start placeholder: {$command}\n";
