<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/support/helpers.php';

use app\service\auth\AuthService;

$service = new AuthService();
$data = $service->login('admin', 'admin123');

if (($data['default_portal'] ?? '') !== 'admin') {
    fwrite(STDERR, "default portal mismatch\n");
    exit(1);
}

echo "auth admin mock smoke ok\n";
