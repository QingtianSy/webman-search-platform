<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/support/helpers.php';

use app\repository\mysql\MenuRepository;

$list = (new MenuRepository())->all();
if (!is_array($list) || count($list) === 0) {
    fwrite(STDERR, "menus empty\n");
    exit(1);
}

echo "menu mock smoke ok\n";
