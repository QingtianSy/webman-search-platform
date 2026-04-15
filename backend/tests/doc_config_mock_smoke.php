<?php

require_once dirname(__DIR__) . '/support/helpers.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

use app\repository\mysql\DocConfigRepository;

$config = (new DocConfigRepository())->get();

if (($config['multimodal_model'] ?? '') === '') {
    fwrite(STDERR, "doc config missing multimodal model\n");
    exit(1);
}

echo "doc config mock smoke ok\n";
