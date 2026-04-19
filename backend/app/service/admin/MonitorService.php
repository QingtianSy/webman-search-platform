<?php

namespace app\service\admin;

use app\service\system\HealthService;
use support\adapter\MySqlClient;
use support\adapter\RedisClient;

class MonitorService
{
    public function overview(): array
    {
        return [
            'server'   => $this->serverInfo(),
            'php'      => $this->phpInfo(),
            'services' => (new HealthService())->detail()['services'] ?? [],
            'redis'    => $this->redisInfo(),
            'database' => $this->databaseInfo(),
            'business' => $this->businessInfo(),
        ];
    }

    protected function serverInfo(): array
    {
        $pidFile = runtime_path() . '/webman.pid';
        $startTime = is_file($pidFile) ? filemtime($pidFile) : null;

        return [
            'hostname'           => gethostname(),
            'os'                 => PHP_OS_FAMILY . ' ' . php_uname('r'),
            'php_version'        => PHP_VERSION,
            'sapi'               => PHP_SAPI,
            'worker_count'       => (int) config('server.count', 1),
            'swoole_version'     => extension_loaded('swoole') ? swoole_version() : null,
            'start_time'         => $startTime ? date('Y-m-d H:i:s', $startTime) : null,
            'uptime_seconds'     => $startTime ? time() - $startTime : null,
            'load_average'       => function_exists('sys_getloadavg') ? sys_getloadavg() : null,
        ];
    }

    protected function phpInfo(): array
    {
        return [
            'memory_usage'  => $this->formatBytes(memory_get_usage(true)),
            'memory_peak'   => $this->formatBytes(memory_get_peak_usage(true)),
            'memory_limit'  => ini_get('memory_limit'),
            'max_execution' => ini_get('max_execution_time'),
            'extensions'    => [
                'swoole'    => extension_loaded('swoole'),
                'redis'     => extension_loaded('redis'),
                'mongodb'   => extension_loaded('mongodb'),
                'pdo_mysql' => extension_loaded('pdo_mysql'),
                'curl'      => extension_loaded('curl'),
                'mbstring'  => extension_loaded('mbstring'),
                'bcmath'    => extension_loaded('bcmath'),
            ],
        ];
    }

    protected function redisInfo(): ?array
    {
        $redis = RedisClient::connection();
        if (!$redis) {
            return null;
        }
        try {
            $server  = $redis->info('server');
            $clients = $redis->info('clients');
            $memory  = $redis->info('memory');
            $stats   = $redis->info('stats');
            $keyspace = $redis->info('keyspace');

            return [
                'redis_version'     => $server['redis_version'] ?? null,
                'uptime_in_seconds' => (int) ($server['uptime_in_seconds'] ?? 0),
                'connected_clients' => (int) ($clients['connected_clients'] ?? 0),
                'used_memory_human' => $memory['used_memory_human'] ?? null,
                'used_memory_peak'  => $memory['used_memory_peak_human'] ?? null,
                'keyspace_hits'     => (int) ($stats['keyspace_hits'] ?? 0),
                'keyspace_misses'   => (int) ($stats['keyspace_misses'] ?? 0),
                'total_commands'    => (int) ($stats['total_commands_processed'] ?? 0),
                'db_keys'           => $this->parseKeyspace($keyspace),
            ];
        } catch (\Throwable $e) {
            error_log("[MonitorService] redisInfo failed: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    protected function databaseInfo(): ?array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return null;
        }
        try {
            $status = [];
            $rows = $pdo->query("SHOW GLOBAL STATUS WHERE Variable_name IN ('Threads_connected','Questions','Slow_queries','Uptime','Connections','Bytes_sent','Bytes_received')")->fetchAll(\PDO::FETCH_KEY_PAIR);
            $vars = $pdo->query("SHOW VARIABLES WHERE Variable_name IN ('max_connections','version')")->fetchAll(\PDO::FETCH_KEY_PAIR);

            return [
                'version'           => $vars['version'] ?? null,
                'uptime_seconds'    => (int) ($rows['Uptime'] ?? 0),
                'threads_connected' => (int) ($rows['Threads_connected'] ?? 0),
                'max_connections'   => (int) ($vars['max_connections'] ?? 0),
                'total_queries'     => (int) ($rows['Questions'] ?? 0),
                'slow_queries'      => (int) ($rows['Slow_queries'] ?? 0),
                'total_connections' => (int) ($rows['Connections'] ?? 0),
                'bytes_sent'        => $this->formatBytes((int) ($rows['Bytes_sent'] ?? 0)),
                'bytes_received'    => $this->formatBytes((int) ($rows['Bytes_received'] ?? 0)),
            ];
        } catch (\Throwable $e) {
            error_log("[MonitorService] databaseInfo failed: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    protected function businessInfo(): array
    {
        $dashboard = (new DashboardAdminService())->overview();

        $pdo = MySqlClient::pdo();
        $activeTasks = 0;
        if ($pdo) {
            try {
                $activeTasks = (int) $pdo->query("SELECT COUNT(*) FROM collect_tasks WHERE status = 1")->fetchColumn();
            } catch (\Throwable) {
            }
        }

        return $dashboard + ['active_collect_tasks' => $activeTasks];
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        $units = ['KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $value = $bytes / 1024;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }
        return round($value, 2) . ' ' . $units[$i];
    }

    protected function parseKeyspace(array $info): array
    {
        $result = [];
        foreach ($info as $key => $val) {
            if (str_starts_with($key, 'db') && is_string($val)) {
                $parts = [];
                foreach (explode(',', $val) as $pair) {
                    [$k, $v] = explode('=', $pair, 2) + [1 => ''];
                    $parts[$k] = $v;
                }
                $result[$key] = (int) ($parts['keys'] ?? 0);
            }
        }
        return $result;
    }
}
