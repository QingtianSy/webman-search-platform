<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/support/helpers.php';

use app\repository\mysql\RoleRepository;
use app\repository\mysql\PermissionRepository;
use app\repository\mysql\MenuRepository;

$roles = (new RoleRepository())->all();
$permissions = (new PermissionRepository())->all();
$menus = (new MenuRepository())->all();

if (count($roles) === 0) {
    fwrite(STDERR, "roles empty\n");
    exit(1);
}
if (count($permissions) === 0) {
    fwrite(STDERR, "permissions empty\n");
    exit(2);
}
if (count($menus) === 0) {
    fwrite(STDERR, "menus empty\n");
    exit(3);
}

echo "rbac mock smoke ok\n";
