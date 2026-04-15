<?php

require_once dirname(__DIR__) . '/support/helpers.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

use app\service\question\QuestionService;

$service = new QuestionService();
$result = $service->detail(100001);

if (($result['question_id'] ?? 0) !== 100001) {
    fwrite(STDERR, "question detail mismatch\n");
    exit(1);
}

echo "question detail mock smoke ok\n";
