<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/support/helpers.php';

use app\controller\HealthController;

$controller = new HealthController();
$health = $controller->health();
$ready = $controller->ready();

if (!method_exists($health, 'rawBody') || !method_exists($ready, 'rawBody')) {
    fwrite(STDERR, "health response invalid\n");
    exit(1);
}

$healthData = json_decode($health->rawBody(), true);
$readyData = json_decode($ready->rawBody(), true);

if (($healthData['code'] ?? 0) !== 1) {
    fwrite(STDERR, "health failed\n");
    exit(2);
}
if (($readyData['code'] ?? 0) !== 1) {
    fwrite(STDERR, "ready failed\n");
    exit(3);
}

echo "health mock smoke ok\n";
