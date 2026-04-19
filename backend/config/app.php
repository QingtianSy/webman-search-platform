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

use support\Request;

return [
    'debug' => filter_var(function_exists('env') ? env('APP_DEBUG', false) : false, FILTER_VALIDATE_BOOLEAN),
    'error_reporting' => E_ALL,
    'default_timezone' => 'Asia/Shanghai',
    'request_class' => Request::class,
    'public_path' => base_path() . DIRECTORY_SEPARATOR . 'public',
    'runtime_path' => base_path(false) . DIRECTORY_SEPARATOR . 'runtime',
    'controller_suffix' => 'Controller',
    'controller_reuse' => false,
    'app_name' => function_exists('env') ? env('APP_NAME', 'webman-search-platform') : 'webman-search-platform',
    'env' => function_exists('env') ? env('APP_ENV', 'prod') : 'prod',
];
