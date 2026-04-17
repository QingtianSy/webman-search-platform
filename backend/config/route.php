<?php

use Webman\Route;
use app\controller\HealthController;
use app\controller\auth\AuthController as UnifiedAuthController;
use app\controller\user\DashboardController;
use app\controller\user\SearchController as UserSearchController;
use app\controller\admin\QuestionController;
use app\controller\open\SearchController as OpenSearchController;
use app\controller\open\HealthController as OpenHealthController;

Route::get('/health', [HealthController::class, 'health']);
Route::get('/ready', [HealthController::class, 'ready']);

Route::post('/api/v1/auth/login', [UnifiedAuthController::class, 'login']);
Route::get('/api/v1/auth/profile', [UnifiedAuthController::class, 'profile']);
Route::get('/api/v1/auth/menus', [UnifiedAuthController::class, 'menus']);
Route::get('/api/v1/auth/permissions', [UnifiedAuthController::class, 'permissions']);

Route::get('/api/v1/user/dashboard/overview', [DashboardController::class, 'overview']);
Route::post('/api/v1/user/search/query', [UserSearchController::class, 'query']);
Route::get('/api/v1/user/search/logs', [UserSearchController::class, 'logs']);

Route::get('/api/v1/admin/question/list', [QuestionController::class, 'index']);
Route::get('/api/v1/admin/question/detail', [QuestionController::class, 'detail']);

Route::post('/open/v1/search/query', [OpenSearchController::class, 'query']);
Route::get('/open/v1/health', [OpenHealthController::class, 'index']);
