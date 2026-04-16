<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/support/helpers.php';

use app\repository\mysql\SystemConfigRepository;

$list = (new SystemConfigRepository())->all();
if (!is_array($list) || count($list) === 0) {
    fwrite(STDERR, "system config empty\n");
    exit(1);
}

echo "system config mock smoke ok\n";
