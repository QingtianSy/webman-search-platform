<?php

require_once dirname(__DIR__) . '/support/helpers.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

use app\service\open\ApiKeyService;

$service = new ApiKeyService();
$list = $service->listByUserId(1);

if (!is_array($list) || empty($list)) {
    fwrite(STDERR, "api key list empty\n");
    exit(1);
}

if (($list[0]['api_key'] ?? '') === '') {
    fwrite(STDERR, "api key missing\n");
    exit(2);
}

echo "api key mock smoke ok\n";
