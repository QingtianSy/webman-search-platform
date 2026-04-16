#!/usr/bin/env php
<?php

chdir(__DIR__);

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    fwrite(STDERR, "Run 'composer install' first.\n");
    exit(1);
}

require_once __DIR__ . '/vendor/autoload.php';

// 后续真实接入时，此处替换为：
// support\App::run();

echo "webman start placeholder (replace with support\\App::run() after real integration)\n";
