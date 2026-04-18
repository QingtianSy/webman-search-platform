#!/usr/bin/env php
<?php
chdir(__DIR__);
require_once __DIR__ . '/vendor/autoload.php';

if (extension_loaded('swoole')) {
    \Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_ALL);
}

support\App::run();
