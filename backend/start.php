#!/usr/bin/env php
<?php

chdir(__DIR__);

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    fwrite(STDERR, "Run 'composer install' first.\n");
    exit(1);
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/support/bootstrap.php';

support\App::run();
