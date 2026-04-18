<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/support/helpers.php';

use app\service\admin\AnnouncementAdminService;
use app\service\admin\ApiSourceAdminService;
use app\service\admin\CollectAdminService;
use app\service\admin\DocAdminService;
use app\service\admin\MenuAdminService;
use app\service\admin\PermissionAdminService;
use app\service\admin\PlanAdminService;
use app\service\admin\RoleAdminService;
use app\service\admin\SystemConfigAdminService;
use app\service\admin\UserAdminService;

$targets = [
    'users' => (new UserAdminService())->getList(['page' => 1, 'page_size' => 20, 'keyword' => '', 'status' => null]),
    'roles' => (new RoleAdminService())->getList(['page' => 1, 'page_size' => 20, 'keyword' => '', 'status' => null]),
    'permissions' => (new PermissionAdminService())->getList(['page' => 1, 'page_size' => 20, 'keyword' => '', 'status' => null]),
    'menus' => (new MenuAdminService())->getList(['page' => 1, 'page_size' => 20, 'keyword' => '', 'status' => null]),
    'announcements' => (new AnnouncementAdminService())->getList(['page' => 1, 'page_size' => 20, 'keyword' => '', 'status' => null]),
    'system_config' => (new SystemConfigAdminService())->getList(['page' => 1, 'page_size' => 20, 'keyword' => '', 'status' => null]),
    'plans' => (new PlanAdminService())->getList(['page' => 1, 'page_size' => 20, 'keyword' => '', 'status' => null]),
    'docs' => (new DocAdminService())->getList(['page' => 1, 'page_size' => 20, 'keyword' => '', 'status' => null]),
    'collect' => (new CollectAdminService())->getList(['page' => 1, 'page_size' => 20, 'keyword' => '', 'status' => null]),
    'api_source' => (new ApiSourceAdminService())->getList(['page' => 1, 'page_size' => 20, 'keyword' => '', 'status' => null]),
];

foreach ($targets as $name => $payload) {
    if (!is_array($payload) || !array_key_exists('list', $payload)) {
        fwrite(STDERR, $name . " admin module smoke failed\n");
        exit(1);
    }
    if (!array_key_exists('page', $payload) || !array_key_exists('page_size', $payload)) {
        fwrite(STDERR, $name . " admin pagination fields missing\n");
        exit(2);
    }
}

// 典型后台查询参数回归验证
$filtered = (new UserAdminService())->getList(['page' => 1, 'page_size' => 20, 'keyword' => 'admin', 'status' => 1]);
if (!is_array($filtered) || !array_key_exists('list', $filtered)) {
    fwrite(STDERR, "admin query filter smoke failed\n");
    exit(3);
}

echo "admin modules mock smoke ok\n";
