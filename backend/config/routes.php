<?php

use app\controller\HealthController;
use app\controller\admin\AnnouncementController;
use app\controller\admin\ApiSourceManageController;
use app\controller\admin\AuthController as AdminAuthController;
use app\controller\admin\CollectManageController;
use app\controller\admin\DocManageController;
use app\controller\admin\PlanController;
use app\controller\admin\QuestionController;
use app\controller\admin\SearchLogController as AdminSearchLogController;
use app\controller\admin\SystemConfigController;
use app\controller\admin\UserController;
use app\controller\auth\AuthController as UnifiedAuthController;
use app\controller\open\HealthController as OpenHealthController;
use app\controller\open\SearchController as OpenSearchController;
use app\controller\user\ApiKeyController;
use app\controller\user\AuthController as UserAuthController;
use app\controller\user\BillingController;
use app\controller\user\CollectController;
use app\controller\user\DashboardController;
use app\controller\user\DocController;
use app\controller\user\LogController;
use app\controller\user\SearchController as UserSearchController;

return [
    ['GET', '/health', [HealthController::class, 'health']],
    ['GET', '/ready', [HealthController::class, 'ready']],

    ['POST', '/api/v1/auth/login', [UnifiedAuthController::class, 'login']],
    ['GET', '/api/v1/auth/profile', [UnifiedAuthController::class, 'profile']],

    ['POST', '/api/v1/user/auth/login', [UserAuthController::class, 'login']],
    ['GET', '/api/v1/user/auth/profile', [UserAuthController::class, 'profile']],
    ['GET', '/api/v1/user/dashboard/overview', [DashboardController::class, 'overview']],
    ['GET', '/api/v1/user/api-key/list', [ApiKeyController::class, 'index']],
    ['GET', '/api/v1/user/api-key/detail', [ApiKeyController::class, 'detail']],
    ['POST', '/api/v1/user/api-key/create', [ApiKeyController::class, 'create']],
    ['POST', '/api/v1/user/api-key/toggle', [ApiKeyController::class, 'toggle']],
    ['GET', '/api/v1/user/wallet/detail', [BillingController::class, 'wallet']],
    ['GET', '/api/v1/user/plan/current', [BillingController::class, 'currentPlan']],
    ['GET', '/api/v1/user/doc/category/list', [DocController::class, 'categories']],
    ['GET', '/api/v1/user/doc/article/detail', [DocController::class, 'detail']],
    ['GET', '/api/v1/user/doc/config', [DocController::class, 'config']],
    ['GET', '/api/v1/user/collect/account/list', [CollectController::class, 'accounts']],
    ['GET', '/api/v1/user/collect/task/list', [CollectController::class, 'tasks']],
    ['GET', '/api/v1/user/collect/task/detail', [CollectController::class, 'detail']],
    ['GET', '/api/v1/user/log/balance', [LogController::class, 'balance']],
    ['GET', '/api/v1/user/log/payment', [LogController::class, 'payment']],
    ['GET', '/api/v1/user/log/login', [LogController::class, 'login']],
    ['GET', '/api/v1/user/log/operate', [LogController::class, 'operate']],
    ['POST', '/api/v1/user/search/query', [UserSearchController::class, 'query']],
    ['GET', '/api/v1/user/search/logs', [UserSearchController::class, 'logs']],

    ['POST', '/api/v1/admin/auth/login', [AdminAuthController::class, 'login']],
    ['GET', '/api/v1/admin/auth/profile', [AdminAuthController::class, 'profile']],
    ['GET', '/api/v1/admin/question/list', [QuestionController::class, 'index']],
    ['POST', '/api/v1/admin/question/create', [QuestionController::class, 'create']],
    ['GET', '/api/v1/admin/user/list', [UserController::class, 'index']],
    ['GET', '/api/v1/admin/plan/list', [PlanController::class, 'index']],
    ['GET', '/api/v1/admin/announcement/list', [AnnouncementController::class, 'index']],
    ['GET', '/api/v1/admin/log/search/list', [AdminSearchLogController::class, 'index']],
    ['GET', '/api/v1/admin/doc/article/list', [DocManageController::class, 'articles']],
    ['GET', '/api/v1/admin/collect/task/list', [CollectManageController::class, 'tasks']],
    ['GET', '/api/v1/admin/collect/task/detail', [CollectManageController::class, 'detail']],
    ['GET', '/api/v1/admin/api-source/list', [ApiSourceManageController::class, 'index']],
    ['GET', '/api/v1/admin/api-source/detail', [ApiSourceManageController::class, 'detail']],
    ['GET', '/api/v1/admin/system-config/list', [SystemConfigController::class, 'index']],

    ['POST', '/open/v1/search/query', [OpenSearchController::class, 'query']],
    ['GET', '/open/v1/health', [OpenHealthController::class, 'index']],
];
