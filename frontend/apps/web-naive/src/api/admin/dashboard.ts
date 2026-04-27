import { requestClient } from '#/api/request';

/**
 * 管理端 Dashboard 概览。对齐后端 [backend/app/controller/admin/DashboardController.php](../../../../../../backend/app/controller/admin/DashboardController.php)。
 *   - GET /admin/dashboard/overview
 *   - GET /admin/dashboard/trend     🆕 Phase 2 末尾补
 *   - GET /admin/dashboard/todo      🆕 Phase 2 末尾补
 *   - GET /admin/dashboard/activity  🆕 Phase 2 末尾补
 *
 * 数值语义：
 *   - total_order_amount / today_order_amount 为 formatted 字符串（保留 2 位小数，前端直接展示即可）
 *   - 其它整数字段（total_users 等）为 int
 *   - total_questions 来自 Mongo；任一数据源不可用时后端抛 50001（请求拦截器已弹红 banner，前端页面显示骨架态即可）
 */
export namespace AdminDashboardApi {
  export interface Overview {
    total_users: number;
    today_users: number;
    total_searches: number;
    today_searches: number;
    total_order_amount: string;
    today_order_amount: string;
    total_questions: number;
  }

  // 趋势：近 7/30 天两条折线
  export interface TrendPoint {
    date: string;
    users: number;
    searches: number;
    amount: number | string;
  }

  export interface Trend {
    list: TrendPoint[];
  }

  // 待办红点
  export interface Todo {
    pending_orders: number; // 24h 未支付订单
    failed_payments: number; // 支付失败待核对
    new_announcements: number; // 未发布公告草稿
    low_proxies: number; // 可用代理 < 阈值
    es_out_of_sync: number; // 未同步题目
  }

  // 实时动态（最近搜索 + 最近交易）
  export interface ActivityItem {
    type: 'login' | 'order' | 'search';
    user_id: number;
    username?: string;
    summary: string;
    created_at: string;
  }

  export interface Activity {
    searches: ActivityItem[];
    orders: ActivityItem[];
  }
}

export async function getAdminOverviewApi() {
  return requestClient.get<AdminDashboardApi.Overview>(
    '/admin/dashboard/overview',
  );
}

export async function getAdminDashboardTrendApi(days: 30 | 7 = 7) {
  return requestClient.get<AdminDashboardApi.Trend>('/admin/dashboard/trend', {
    params: { days },
  });
}

export async function getAdminDashboardTodoApi() {
  return requestClient.get<AdminDashboardApi.Todo>('/admin/dashboard/todo');
}

export async function getAdminDashboardActivityApi() {
  return requestClient.get<AdminDashboardApi.Activity>(
    '/admin/dashboard/activity',
  );
}
