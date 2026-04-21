<?php

namespace support;

// 热路径专用的轻量日志入口。
//
// 背景：审查发现 middleware / 搜索 / 鉴权缓存 / 限流 等热路径大量使用 `error_log()`。
// 在 Workerman 下 `error_log()` 走 stderr → 父进程 pipe → 重写磁盘，每条开销不小，
// 并且相同 worker 的多次调用不能复用文件句柄。
//
// 这里用进程级静态资源持有一个追加写句柄（只打开一次），写入 runtime/logs/app.log，
// 避免重复 open/close。非热路径仍可继续用 error_log，无需大范围改造。
//
// 注意：Workerman 是常驻进程模型，句柄在 worker 存活期内保持打开；日志文件被 logrotate
// 轮转时，logrotate 的 `create` + `delaycompress + postrotate reload` 组合配合 SIGHUP
// 可以触发 worker 重启重开句柄。若运维未配置，使用 Monolog RotatingFileHandler 更稳，
// 但那需要引入 `support\Log::channel('app')`，本期只迁移热路径故选更轻量的裸句柄方案。
class AppLog
{
    /** @var resource|null */
    private static $handle = null;
    private static string $path = '';

    private static function handle()
    {
        if (self::$handle !== null) {
            return self::$handle;
        }
        if (self::$path === '') {
            // 优先用 Webman 的 runtime_path() helper；fallback 到 BASE_PATH 或相对路径，
            // 避免 helper 未就绪（很早期静态上下文）时崩溃。
            if (function_exists('runtime_path')) {
                self::$path = runtime_path() . '/logs/app.log';
            } elseif (defined('BASE_PATH')) {
                self::$path = BASE_PATH . '/runtime/logs/app.log';
            } else {
                self::$path = dirname(__DIR__) . '/runtime/logs/app.log';
            }
        }
        $dir = dirname(self::$path);
        if (!is_dir($dir)) {
            // suppress: 热路径不抛；失败回落到 error_log 路径
            @mkdir($dir, 0775, true);
        }
        $fp = @fopen(self::$path, 'a');
        if ($fp === false) {
            return null;
        }
        self::$handle = $fp;
        return $fp;
    }

    public static function warn(string $message): void
    {
        self::write('WARN', $message);
    }

    public static function error(string $message): void
    {
        self::write('ERROR', $message);
    }

    public static function info(string $message): void
    {
        self::write('INFO', $message);
    }

    private static function write(string $level, string $message): void
    {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $level . ' ' . $message . PHP_EOL;
        $fp = self::handle();
        if ($fp === null) {
            // 兜底：句柄打不开时回落到 error_log，保证不丢日志。
            error_log($line);
            return;
        }
        // fwrite 追加模式下 O_APPEND 原子；多 worker 并发也不会交织半行。
        @fwrite($fp, $line);
    }
}
