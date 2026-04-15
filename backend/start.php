<?php

declare(strict_types=1);

require_once __DIR__ . '/support/helpers.php';

/**
 * 当前文件是“真接入前的启动占位层”。
 *
 * 当前用途：
 * 1. 统一宿主机脚本入口约定
 * 2. 固定 backend 目录结构
 * 3. 作为后续真实 Webman 启动入口替换点
 *
 * 后续真实接入时：
 * - 本文件将被 Webman 官方启动入口实现接管
 * - systemd / 宿主机启动命令仍可保持 `php start.php start`
 */

$command = $argv[1] ?? 'help';

if ($command === 'help') {
    echo "webman-search-platform placeholder start script\n";
    echo "Available placeholder commands: start | stop | restart | reload | status\n";
    exit(0);
}

echo "[placeholder] command: {$command}\n";
exit(0);
