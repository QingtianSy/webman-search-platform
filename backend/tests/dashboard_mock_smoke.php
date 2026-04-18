<?php

require_once dirname(__DIR__) . '/support/helpers.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

use app\service\user\DashboardService;

$service = new DashboardService();
$result = $service->overview(1);

if (($result['current_plan']['name'] ?? '') === '') {
    fwrite(STDERR, "dashboard current plan missing\n");
    exit(1);
}

if (!array_key_exists('balance', $result)) {
    fwrite(STDERR, "dashboard balance missing\n");
    exit(2);
}

echo "dashboard mock smoke ok\n";
