import { requestClient } from '#/api/request';

/**
 * 管理端 Dashboard 概览。对齐后端 [backend/app/controller/admin/DashboardController.php](../../../../../../backend/app/controller/admin/DashboardController.php)。
 *   - GET /admin/dashboard/overview
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
}

export async function getAdminOverviewApi() {
  return requestClient.get<AdminDashboardApi.Overview>(
    '/admin/dashboard/overview',
  );
}
