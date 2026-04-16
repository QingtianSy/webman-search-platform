<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/support/helpers.php';

use app\controller\HealthController;

$controller = new HealthController();
$health = $controller->health();
$ready = $controller->ready();

if (($health['code'] ?? 0) !== 1) {
    fwrite(STDERR, "health failed\n");
    exit(1);
}
if (($ready['code'] ?? 0) !== 1) {
    fwrite(STDERR, "ready failed\n");
    exit(2);
}

echo "health mock smoke ok\n";
