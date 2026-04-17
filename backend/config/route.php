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

Route::get('/', [IndexController::class, 'index']);
Route::get('/health', [HealthController::class, 'health']);
Route::get('/ready', [HealthController::class, 'ready']);

Route::post('/api/v1/auth/login', [UnifiedAuthController::class, 'login']);
Route::get('/api/v1/auth/profile', [UnifiedAuthController::class, 'profile']);
Route::get('/api/v1/auth/menus', [UnifiedAuthController::class, 'menus']);
Route::get('/api/v1/auth/permissions', [UnifiedAuthController::class, 'permissions']);

Route::get('/api/v1/user/dashboard/overview', [DashboardController::class, 'overview']);
Route::get('/api/v1/user/api-key/list', [ApiKeyController::class, 'index']);
Route::get('/api/v1/user/api-key/detail', [ApiKeyController::class, 'detail']);
Route::post('/api/v1/user/api-key/create', [ApiKeyController::class, 'create']);
Route::post('/api/v1/user/api-key/toggle', [ApiKeyController::class, 'toggle']);
Route::delete('/api/v1/user/api-key/delete', [ApiKeyController::class, 'delete']);
Route::get('/api/v1/user/wallet/detail', [BillingController::class, 'wallet']);
Route::get('/api/v1/user/plan/current', [BillingController::class, 'currentPlan']);
Route::get('/api/v1/user/doc/category/list', [DocController::class, 'categories']);
Route::get('/api/v1/user/doc/article/detail', [DocController::class, 'detail']);
Route::get('/api/v1/user/doc/config', [DocController::class, 'config']);
Route::get('/api/v1/user/collect/task/list', [CollectController::class, 'tasks']);
Route::get('/api/v1/user/collect/task/detail', [CollectController::class, 'detail']);
Route::get('/api/v1/user/log/balance', [LogController::class, 'balance']);
Route::get('/api/v1/user/log/payment', [LogController::class, 'payment']);
Route::get('/api/v1/user/log/login', [LogController::class, 'login']);
Route::get('/api/v1/user/log/operate', [LogController::class, 'operate']);
Route::post('/api/v1/user/search/query', [UserSearchController::class, 'query']);
Route::get('/api/v1/user/search/logs', [UserSearchController::class, 'logs']);

Route::get('/api/v1/admin/question/list', [QuestionController::class, 'index']);
Route::get('/api/v1/admin/question/detail', [QuestionController::class, 'detail']);
Route::post('/api/v1/admin/question/create', [QuestionController::class, 'create']);
Route::put('/api/v1/admin/question/update', [QuestionController::class, 'update']);
Route::delete('/api/v1/admin/question/delete', [QuestionController::class, 'delete']);
Route::get('/api/v1/admin/question-category/list', [QuestionCategoryController::class, 'index']);
Route::get('/api/v1/admin/question-type/list', [QuestionTypeController::class, 'index']);
Route::get('/api/v1/admin/question-source/list', [QuestionSourceController::class, 'index']);
Route::get('/api/v1/admin/question-tag/list', [QuestionTagController::class, 'index']);
Route::get('/api/v1/admin/user/list', [UserController::class, 'index']);
Route::get('/api/v1/admin/role/list', [RoleController::class, 'index']);
Route::get('/api/v1/admin/permission/list', [PermissionController::class, 'index']);
Route::get('/api/v1/admin/menu/list', [MenuController::class, 'index']);
Route::get('/api/v1/admin/plan/list', [PlanController::class, 'index']);
Route::get('/api/v1/admin/announcement/list', [AnnouncementController::class, 'index']);
Route::post('/api/v1/admin/announcement/create', [AnnouncementController::class, 'create']);
Route::put('/api/v1/admin/announcement/update', [AnnouncementController::class, 'update']);
Route::delete('/api/v1/admin/announcement/delete', [AnnouncementController::class, 'delete']);
Route::get('/api/v1/admin/log/search/list', [SearchLogController::class, 'index']);
Route::get('/api/v1/admin/doc/article/list', [DocManageController::class, 'articles']);
Route::post('/api/v1/admin/doc/article/create', [DocManageController::class, 'create']);
Route::put('/api/v1/admin/doc/article/update', [DocManageController::class, 'update']);
Route::delete('/api/v1/admin/doc/article/delete', [DocManageController::class, 'delete']);
Route::get('/api/v1/admin/collect/task/list', [CollectManageController::class, 'tasks']);
Route::get('/api/v1/admin/collect/task/detail', [CollectManageController::class, 'detail']);
Route::post('/api/v1/admin/collect/task/stop', [CollectManageController::class, 'stop']);
Route::post('/api/v1/admin/collect/task/retry', [CollectManageController::class, 'retry']);
Route::get('/api/v1/admin/api-source/list', [ApiSourceManageController::class, 'index']);
Route::get('/api/v1/admin/api-source/detail', [ApiSourceManageController::class, 'detail']);
Route::post('/api/v1/admin/api-source/test', [ApiSourceManageController::class, 'test']);
Route::get('/api/v1/admin/system-config/list', [SystemConfigController::class, 'index']);
Route::post('/api/v1/admin/system-config/update', [SystemConfigController::class, 'update']);

Route::post('/open/v1/search/query', [OpenSearchController::class, 'query']);
Route::get('/open/v1/quota/detail', [OpenSearchController::class, 'quotaDetail']);
Route::get('/open/v1/health', [OpenHealthController::class, 'index']);
