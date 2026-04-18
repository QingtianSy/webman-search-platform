<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@163.com>
 * @copyright walkor<walkor@163.com>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use Webman\Route;
use app\middleware\UserAuthMiddleware;
use app\middleware\AdminAuthMiddleware;
use app\controller\HealthController;
use app\controller\IndexController;
use app\controller\auth\AuthController as UnifiedAuthController;
use app\controller\user\ApiKeyController;
use app\controller\user\BillingController;
use app\controller\user\CollectController;
use app\controller\user\DashboardController;
use app\controller\user\DocController;
use app\controller\user\LogController;
use app\controller\user\SearchController as UserSearchController;
use app\controller\admin\AnnouncementController;
use app\controller\admin\ApiSourceManageController;
use app\controller\admin\CollectManageController;
use app\controller\admin\DocManageController;
use app\controller\admin\MenuController;
use app\controller\admin\PermissionController;
use app\controller\admin\PlanController;
use app\controller\admin\QuestionController;
use app\controller\admin\QuestionCategoryController;
use app\controller\admin\QuestionTypeController;
use app\controller\admin\QuestionSourceController;
use app\controller\admin\QuestionTagController;
use app\controller\admin\RoleController;
use app\controller\admin\SearchLogController;
use app\controller\admin\SystemConfigController;
use app\controller\admin\UserController;
use app\controller\open\HealthController as OpenHealthController;
use app\controller\open\SearchController as OpenSearchController;

// 公共路由（无需认证）
Route::get('/', [IndexController::class, 'index']);
Route::get('/health', [HealthController::class, 'health']);
Route::get('/ready', [HealthController::class, 'ready']);

// 认证路由（无需认证）
Route::post('/api/v1/auth/login', [UnifiedAuthController::class, 'login']);
Route::get('/api/v1/auth/profile', [UnifiedAuthController::class, 'profile']);
Route::get('/api/v1/auth/menus', [UnifiedAuthController::class, 'menus']);
Route::get('/api/v1/auth/permissions', [UnifiedAuthController::class, 'permissions']);

// 用户端路由（需要用户认证）
Route::group('/api/v1/user', function () {
    Route::get('/dashboard/overview', [DashboardController::class, 'overview']);
    Route::get('/api-key/list', [ApiKeyController::class, 'index']);
    Route::get('/api-key/detail', [ApiKeyController::class, 'detail']);
    Route::post('/api-key/create', [ApiKeyController::class, 'create']);
    Route::post('/api-key/toggle', [ApiKeyController::class, 'toggle']);
    Route::delete('/api-key/delete', [ApiKeyController::class, 'delete']);
    Route::get('/wallet/detail', [BillingController::class, 'wallet']);
    Route::get('/plan/current', [BillingController::class, 'currentPlan']);
    Route::get('/doc/category/list', [DocController::class, 'categories']);
    Route::get('/doc/article/detail', [DocController::class, 'detail']);
    Route::get('/doc/config', [DocController::class, 'config']);
    Route::get('/collect/task/list', [CollectController::class, 'tasks']);
    Route::get('/collect/task/detail', [CollectController::class, 'detail']);
    Route::get('/log/balance', [LogController::class, 'balance']);
    Route::get('/log/payment', [LogController::class, 'payment']);
    Route::get('/log/login', [LogController::class, 'login']);
    Route::get('/log/operate', [LogController::class, 'operate']);
    Route::post('/search/query', [UserSearchController::class, 'query']);
    Route::get('/search/logs', [UserSearchController::class, 'logs']);
})->middleware([UserAuthMiddleware::class]);

// 管理端路由（需要管理员认证）
Route::group('/api/v1/admin', function () {
    Route::get('/question/list', [QuestionController::class, 'index']);
    Route::get('/question/detail', [QuestionController::class, 'detail']);
    Route::post('/question/create', [QuestionController::class, 'create']);
    Route::put('/question/update', [QuestionController::class, 'update']);
    Route::delete('/question/delete', [QuestionController::class, 'delete']);
    Route::get('/question-category/list', [QuestionCategoryController::class, 'index']);
    Route::get('/question-type/list', [QuestionTypeController::class, 'index']);
    Route::get('/question-source/list', [QuestionSourceController::class, 'index']);
    Route::get('/question-tag/list', [QuestionTagController::class, 'index']);
    Route::get('/user/list', [UserController::class, 'index']);
    Route::get('/role/list', [RoleController::class, 'index']);
    Route::get('/permission/list', [PermissionController::class, 'index']);
    Route::get('/menu/list', [MenuController::class, 'index']);
    Route::get('/plan/list', [PlanController::class, 'index']);
    Route::get('/announcement/list', [AnnouncementController::class, 'index']);
    Route::post('/announcement/create', [AnnouncementController::class, 'create']);
    Route::put('/announcement/update', [AnnouncementController::class, 'update']);
    Route::delete('/announcement/delete', [AnnouncementController::class, 'delete']);
    Route::get('/log/search/list', [SearchLogController::class, 'index']);
    Route::get('/doc/article/list', [DocManageController::class, 'articles']);
    Route::post('/doc/article/create', [DocManageController::class, 'create']);
    Route::put('/doc/article/update', [DocManageController::class, 'update']);
    Route::delete('/doc/article/delete', [DocManageController::class, 'delete']);
    Route::get('/collect/task/list', [CollectManageController::class, 'tasks']);
    Route::get('/collect/task/detail', [CollectManageController::class, 'detail']);
    Route::post('/collect/task/stop', [CollectManageController::class, 'stop']);
    Route::post('/collect/task/retry', [CollectManageController::class, 'retry']);
    Route::get('/api-source/list', [ApiSourceManageController::class, 'index']);
    Route::get('/api-source/detail', [ApiSourceManageController::class, 'detail']);
    Route::post('/api-source/test', [ApiSourceManageController::class, 'test']);
    Route::get('/system-config/list', [SystemConfigController::class, 'index']);
    Route::post('/system-config/update', [SystemConfigController::class, 'update']);
})->middleware([AdminAuthMiddleware::class]);

// 开放 API 路由（无需认证或使用 API Key 认证）
Route::post('/open/v1/search/query', [OpenSearchController::class, 'query']);
Route::get('/open/v1/quota/detail', [OpenSearchController::class, 'quotaDetail']);
Route::get('/open/v1/health', [OpenHealthController::class, 'index']);
