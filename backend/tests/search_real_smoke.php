<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/support/helpers.php';

use app\repository\es\QuestionIndexRepository;

$repo = new QuestionIndexRepository();
$result = $repo->search('Redis');

if (!is_array($result)) {
    fwrite(STDERR, "search real smoke failed\n");
    exit(1);
}

echo "search real smoke skeleton ok\n";
