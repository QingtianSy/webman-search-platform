<?php

require_once dirname(__DIR__) . '/support/helpers.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

use app\service\search\SearchService;

$service = new SearchService();
$result = $service->query('中共中央总书记');

if (!is_array($result) || empty($result['list'])) {
    fwrite(STDERR, "search result empty\n");
    exit(1);
}

$first = $result['list'][0] ?? [];
if (($first['question_id'] ?? 0) !== 100001) {
    fwrite(STDERR, "unexpected search question id\n");
    exit(2);
}

echo "search mock smoke ok\n";
