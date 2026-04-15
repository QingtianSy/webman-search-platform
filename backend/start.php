<?php

declare(strict_types=1);

require_once __DIR__ . '/support/helpers.php';

/**
 * start.php
 *
 * Phase 1 真实接入准备版：
 * - 检查 vendor/autoload.php
 * - 维持宿主机 `php start.php start` 约定
 * - 后续由真实 Webman 启动入口实现接管
 */

$command = $argv[1] ?? 'help';
$autoload = __DIR__ . '/vendor/autoload.php';

if (!is_file($autoload)) {
    echo "Dependencies not installed. Please run scripts/prepare_backend_dependencies.sh first.\n";
    exit($command === 'help' ? 0 : 1);
}

require $autoload;

if ($command === 'help') {
    echo "webman-search-platform phase-1 placeholder\n";
    echo "Available placeholder commands: start | stop | restart | reload | status\n";
    exit(0);
}

echo "[phase-1 placeholder] command: {$command}\n";
exit(0);
