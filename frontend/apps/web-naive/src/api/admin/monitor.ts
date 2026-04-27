import { requestClient } from '#/api/request';

/**
 * 管理端 · 监控。对齐后端 [backend/app/controller/admin/MonitorController.php](../../../../../../backend/app/controller/admin/MonitorController.php)。
 *   - GET  /admin/monitor/overview
 *   - GET  /admin/monitor/logs   🆕 Phase 2 末尾补
 */
export namespace AdminMonitorApi {
  export interface ServerInfo {
    hostname?: string;
    os?: string;
    php_version?: string;
    sapi?: string;
    worker_count?: number;
    swoole_version?: null | string;
    start_time?: null | string;
    uptime_seconds?: null | number;
    load_average?: null | [number, number, number];
  }

  export interface PhpInfo {
    memory_usage?: string;
    memory_peak?: string;
    memory_limit?: string;
    max_execution?: number | string;
    extensions?: Record<string, boolean>;
  }

  export interface RedisInfo {
    redis_version?: null | string;
    uptime_in_seconds?: number;
    connected_clients?: number;
    used_memory_human?: null | string;
    used_memory_peak?: null | string;
    keyspace_hits?: number;
    keyspace_misses?: number;
    total_commands?: number;
    db_keys?: Record<string, number>;
    error?: string;
  }

  export interface DatabaseInfo {
    version?: null | string;
    uptime_seconds?: number;
    threads_connected?: number;
    max_connections?: number;
    total_queries?: number;
    slow_queries?: number;
    total_connections?: number;
    bytes_sent?: string;
    bytes_received?: string;
    error?: string;
  }

  export type ServiceKey = 'elasticsearch' | 'mongodb' | 'mysql' | 'redis';
  // 取值来自后端 HealthService：'ok' | 'error' | 'disconnected' | 'not_configured' | 'unknown'
  export type ServicesMap = Partial<Record<ServiceKey, string>>;

  export interface BusinessInfo {
    total_users?: number;
    today_users?: number;
    total_searches?: number;
    today_searches?: number;
    total_order_amount?: string;
    today_order_amount?: string;
    total_questions?: number;
    active_collect_tasks?: null | number;
    error?: string;
  }

  export interface Overview {
    server?: ServerInfo;
    php?: PhpInfo;
    services?: ServicesMap;
    redis?: null | RedisInfo;
    database?: DatabaseInfo | null;
    business?: BusinessInfo;
  }

  export interface LogEntry {
    timestamp: string;
    level: string;
    message: string;
    channel?: string;
    context?: any;
  }
}

export async function getMonitorOverviewApi() {
  return requestClient.get<AdminMonitorApi.Overview>('/admin/monitor/overview');
}

export async function getMonitorLogsApi(params?: {
  keyword?: string;
  level?: string;
  limit?: number;
}) {
  return requestClient.get<AdminMonitorApi.LogEntry[]>(
    '/admin/monitor/logs',
    { params },
  );
}
