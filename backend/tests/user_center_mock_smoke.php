<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/support/helpers.php';

use app\service\user\DashboardService;

$result = (new DashboardService())->overview(1);

if (($result['current_plan']['remain_quota'] ?? null) === null) {
    fwrite(STDERR, "dashboard real-ready smoke failed\n");
    exit(1);
}

echo "dashboard user-center smoke ok\n";
