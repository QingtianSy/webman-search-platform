<?php

require_once dirname(__DIR__) . '/support/helpers.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

use app\repository\mysql\CollectTaskDetailRepository;

$detail = (new CollectTaskDetailRepository())->findByTaskNo('CT202604150001');

if (($detail['task_no'] ?? '') !== 'CT202604150001') {
    fwrite(STDERR, "collect task detail missing\n");
    exit(1);
}

echo "collect task detail mock smoke ok\n";
