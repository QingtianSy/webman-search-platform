<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/support/helpers.php';

use app\service\admin\MenuAdminService;
use app\service\admin\PermissionAdminService;
use app\service\admin\RoleAdminService;
use app\service\admin\UserAdminService;

$targets = [
    'users' => (new UserAdminService())->getList(),
    'roles' => (new RoleAdminService())->getList(),
    'permissions' => (new PermissionAdminService())->getList(),
    'menus' => (new MenuAdminService())->getList(),
];

foreach ($targets as $name => $payload) {
    if (!is_array($payload) || !array_key_exists('list', $payload)) {
        fwrite(STDERR, $name . " admin module smoke failed\n");
        exit(1);
    }
}

echo "admin modules mock smoke ok\n";
