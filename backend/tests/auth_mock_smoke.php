<?php

require_once dirname(__DIR__) . '/support/helpers.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

use app\service\auth\AuthService;

$service = new AuthService();
$payload = $service->login('demo_user', '123456');

if (!$payload) {
    fwrite(STDERR, "auth login failed\n");
    exit(1);
}

if (($payload['user']['username'] ?? '') !== 'demo_user') {
    fwrite(STDERR, "unexpected auth user\n");
    exit(2);
}

if (!in_array('portal.access', $payload['permissions'] ?? [], true)) {
    fwrite(STDERR, "missing portal.access permission\n");
    exit(3);
}

echo "auth mock smoke ok\n";
