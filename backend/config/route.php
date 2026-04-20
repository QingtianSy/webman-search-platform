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
use app\middleware\OpenApiAuthMiddleware;
use app\middleware\RateLimitMiddleware;
use app\controller\HealthController;
use app\controller\IndexController;
use app\controller\auth\AuthController as UnifiedAuthController;
use app\controller\user\AnnouncementController as UserAnnouncementController;
use app\controller\user\ApiKeyController;
use app\controller\user\ApiSourceController as UserApiSourceController;
use app\controller\user\BillingController;
use app\controller\user\CollectController;
use app\controller\user\DashboardController;
use app\controller\user\DocController;
use app\controller\user\LogController;
use app\controller\user\SearchController as UserSearchController;
use app\controller\admin\AnnouncementController;
use app\controller\admin\ApiSourceManageController;
use app\controller\admin\CollectManageController;
use app\controller\admin\MonitorController;
use app\controller\admin\DashboardController as AdminDashboardController;
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
use app\controller\admin\ProxyController;
use app\controller\admin\CollectConfigController;
use app\controller\admin\PaymentConfigController;
use app\controller\admin\DocConfigController;
use app\controller\user\PaymentController;
use app\controller\PaymentCallbackController;
use app\controller\open\HealthController as OpenHealthController;
use app\controller\open\SearchController as OpenSearchController;

// 公共路由（无需认证）
Route::get('/', [IndexController::class, 'index']);
Route::get('/health', [HealthController::class, 'health']);
Route::get('/ready', [HealthController::class, 'ready']);

// 认证路由
Route::post('/api/v1/auth/login', [UnifiedAuthController::class, 'login'])->middleware([new RateLimitMiddleware(10, 60, 'ip')]);
Route::post('/api/v1/auth/register', [UnifiedAuthController::class, 'register'])->middleware([new RateLimitMiddleware(5, 60, 'ip')]);
// profile/menus/permissions 需要登录后才能访问
Route::get('/api/v1/auth/profile', [UnifiedAuthController::class, 'profile'])->middleware([UserAuthMiddleware::class]);
Route::get('/api/v1/auth/menus', [UnifiedAuthController::class, 'menus'])->middleware([UserAuthMiddleware::class]);
Route::get('/api/v1/auth/permissions', [UnifiedAuthController::class, 'permissions'])->middleware([UserAuthMiddleware::class]);
Route::put('/api/v1/auth/update-profile', [UnifiedAuthController::class, 'updateProfile'])->middleware([UserAuthMiddleware::class]);

// 用户端路由（需要用户认证）
Route::group('/api/v1/user', function () {
    Route::get('/dashboard/overview', [DashboardController::class, 'overview']);
    Route::get('/announcement/list', [UserAnnouncementController::class, 'index']);
    Route::get('/announcement/detail', [UserAnnouncementController::class, 'detail']);
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
    Route::post('/collect/query-courses', [CollectController::class, 'queryCourses'])->middleware([new RateLimitMiddleware(10, 60, 'user')]);
    Route::post('/collect/submit-collect', [CollectController::class, 'submitCollect'])->middleware([new RateLimitMiddleware(10, 60, 'user')]);
    Route::get('/log/balance', [LogController::class, 'balance']);
    Route::get('/log/payment', [LogController::class, 'payment']);
    Route::get('/log/login', [LogController::class, 'login']);
    Route::get('/log/operate', [LogController::class, 'operate']);
    Route::post('/search/query', [UserSearchController::class, 'query'])->middleware([new RateLimitMiddleware(30, 60, 'user')]);
    Route::get('/search/logs', [UserSearchController::class, 'logs']);
    Route::get('/api-source/list', [UserApiSourceController::class, 'index']);
    Route::get('/api-source/detail', [UserApiSourceController::class, 'detail']);
    Route::post('/api-source/create', [UserApiSourceController::class, 'create']);
    Route::put('/api-source/update', [UserApiSourceController::class, 'update']);
    Route::delete('/api-source/delete', [UserApiSourceController::class, 'delete']);
    Route::post('/api-source/test', [UserApiSourceController::class, 'test']);
    Route::post('/order/create', [PaymentController::class, 'create'])->middleware([new RateLimitMiddleware(5, 60, 'user')]);
    Route::get('/order/list', [PaymentController::class, 'list']);
})->middleware([UserAuthMiddleware::class]);

// 管理端路由（需要管理员认证）
Route::group('/api/v1/admin', function () {
    Route::get('/dashboard/overview', [AdminDashboardController::class, 'overview']);
    Route::get('/question/list', [QuestionController::class, 'index']);
    Route::get('/question/detail', [QuestionController::class, 'detail']);
    Route::post('/question/create', [QuestionController::class, 'create']);
    Route::put('/question/update', [QuestionController::class, 'update']);
    Route::delete('/question/delete', [QuestionController::class, 'delete']);
    Route::get('/question/export', [QuestionController::class, 'export']);
    Route::post('/question/reindex', [QuestionController::class, 'reindex']);
    Route::get('/question-category/list', [QuestionCategoryController::class, 'index']);
    Route::post('/question-category/create', [QuestionCategoryController::class, 'create']);
    Route::put('/question-category/update', [QuestionCategoryController::class, 'update']);
    Route::delete('/question-category/delete', [QuestionCategoryController::class, 'delete']);
    Route::get('/question-type/list', [QuestionTypeController::class, 'index']);
    Route::post('/question-type/create', [QuestionTypeController::class, 'create']);
    Route::put('/question-type/update', [QuestionTypeController::class, 'update']);
    Route::delete('/question-type/delete', [QuestionTypeController::class, 'delete']);
    Route::get('/question-source/list', [QuestionSourceController::class, 'index']);
    Route::post('/question-source/create', [QuestionSourceController::class, 'create']);
    Route::put('/question-source/update', [QuestionSourceController::class, 'update']);
    Route::delete('/question-source/delete', [QuestionSourceController::class, 'delete']);
    Route::get('/question-tag/list', [QuestionTagController::class, 'index']);
    Route::post('/question-tag/create', [QuestionTagController::class, 'create']);
    Route::put('/question-tag/update', [QuestionTagController::class, 'update']);
    Route::delete('/question-tag/delete', [QuestionTagController::class, 'delete']);
    Route::get('/user/list', [UserController::class, 'index']);
    Route::post('/user/create', [UserController::class, 'create']);
    Route::put('/user/update', [UserController::class, 'update']);
    Route::delete('/user/delete', [UserController::class, 'delete']);
    Route::put('/user/toggle-status', [UserController::class, 'toggleStatus']);
    Route::put('/user/assign-roles', [UserController::class, 'assignRoles']);
    Route::get('/role/list', [RoleController::class, 'index']);
    Route::post('/role/create', [RoleController::class, 'create']);
    Route::put('/role/update', [RoleController::class, 'update']);
    Route::delete('/role/delete', [RoleController::class, 'delete']);
    Route::put('/role/assign-permissions', [RoleController::class, 'assignPermissions']);
    Route::get('/permission/list', [PermissionController::class, 'index']);
    Route::post('/permission/create', [PermissionController::class, 'create']);
    Route::put('/permission/update', [PermissionController::class, 'update']);
    Route::delete('/permission/delete', [PermissionController::class, 'delete']);
    Route::get('/menu/list', [MenuController::class, 'index']);
    Route::post('/menu/create', [MenuController::class, 'create']);
    Route::put('/menu/update', [MenuController::class, 'update']);
    Route::delete('/menu/delete', [MenuController::class, 'delete']);
    Route::get('/plan/list', [PlanController::class, 'index']);
    Route::post('/plan/create', [PlanController::class, 'create']);
    Route::put('/plan/update', [PlanController::class, 'update']);
    Route::delete('/plan/delete', [PlanController::class, 'delete']);
    Route::get('/announcement/list', [AnnouncementController::class, 'index']);
    Route::post('/announcement/create', [AnnouncementController::class, 'create']);
    Route::put('/announcement/update', [AnnouncementController::class, 'update']);
    Route::delete('/announcement/delete', [AnnouncementController::class, 'delete']);
    Route::get('/log/search/list', [SearchLogController::class, 'index']);
    Route::get('/log/search/export', [SearchLogController::class, 'export']);
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

    // IP管理（系统工具）
    Route::get('/proxy/list', [ProxyController::class, 'list']);
    Route::get('/proxy/detail', [ProxyController::class, 'detail']);
    Route::post('/proxy/create', [ProxyController::class, 'create']);
    Route::put('/proxy/update', [ProxyController::class, 'update']);
    Route::delete('/proxy/delete', [ProxyController::class, 'delete']);
    Route::post('/proxy/probe', [ProxyController::class, 'probe']);
    Route::post('/proxy/quick-add', [ProxyController::class, 'quickAdd']);
    Route::post('/proxy/batch-import', [ProxyController::class, 'batchImport']);
    Route::get('/proxy/batch-export', [ProxyController::class, 'batchExport']);
    Route::post('/proxy/probe-all', [ProxyController::class, 'probeAll']);

    // 采集管理（系统工具）
    Route::get('/collect-config/list', [CollectConfigController::class, 'list']);
    Route::post('/collect-config/update', [CollectConfigController::class, 'update']);

    // 支付配置（系统管理）
    Route::get('/payment-config/list', [PaymentConfigController::class, 'list']);
    Route::post('/payment-config/update', [PaymentConfigController::class, 'update']);

    // 文档配置（含 api_key，走专用入口做脱敏展示与合并写入，禁止通用 system-config 入口修改）
    Route::get('/doc-config/list', [DocConfigController::class, 'list']);
    Route::post('/doc-config/update', [DocConfigController::class, 'update']);

    // 系统监控
    Route::get('/monitor/overview', [MonitorController::class, 'overview']);
})->middleware([AdminAuthMiddleware::class]);

// 开放 API 路由（API Key 认证）
Route::group('/open/v1', function () {
    Route::post('/search/query', [OpenSearchController::class, 'query']);
    Route::get('/quota/detail', [OpenSearchController::class, 'quotaDetail']);
})->middleware([OpenApiAuthMiddleware::class]);
// 开放平台健康检查（无需认证）
Route::get('/open/v1/health', [OpenHealthController::class, 'index']);

// 支付回调（无需认证）
Route::any('/callback/epay/notify', [PaymentCallbackController::class, 'notify']);
Route::get('/callback/epay/return', [PaymentCallbackController::class, 'returnUrl']);
