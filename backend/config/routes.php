<?php

use app\controller\HealthController;
use app\controller\admin\AuthController as AdminAuthController;
use app\controller\admin\QuestionController;
use app\controller\open\HealthController as OpenHealthController;
use app\controller\open\SearchController as OpenSearchController;
use app\controller\user\ApiKeyController;
use app\controller\user\AuthController as UserAuthController;
use app\controller\user\BillingController;
use app\controller\user\DashboardController;
use app\controller\user\SearchController as UserSearchController;

return [
    ['GET', '/health', [HealthController::class, 'health']],
    ['GET', '/ready', [HealthController::class, 'ready']],

    ['POST', '/api/v1/user/auth/login', [UserAuthController::class, 'login']],
    ['GET', '/api/v1/user/auth/profile', [UserAuthController::class, 'profile']],
    ['GET', '/api/v1/user/dashboard/overview', [DashboardController::class, 'overview']],
    ['GET', '/api/v1/user/api-key/list', [ApiKeyController::class, 'index']],
    ['GET', '/api/v1/user/wallet/detail', [BillingController::class, 'wallet']],
    ['GET', '/api/v1/user/plan/current', [BillingController::class, 'currentPlan']],
    ['POST', '/api/v1/user/search/query', [UserSearchController::class, 'query']],
    ['GET', '/api/v1/user/search/logs', [UserSearchController::class, 'logs']],

    ['POST', '/api/v1/admin/auth/login', [AdminAuthController::class, 'login']],
    ['GET', '/api/v1/admin/auth/profile', [AdminAuthController::class, 'profile']],
    ['GET', '/api/v1/admin/question/list', [QuestionController::class, 'index']],
    ['POST', '/api/v1/admin/question/create', [QuestionController::class, 'create']],

    ['POST', '/open/v1/search/query', [OpenSearchController::class, 'query']],
    ['GET', '/open/v1/health', [OpenHealthController::class, 'index']],
];
