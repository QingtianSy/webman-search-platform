import { requestClient } from '#/api/request';

/**
 * 管理端日志聚合 API。对齐后端：
 *   - [backend/app/controller/admin/SearchLogController.php](../../../../../../backend/app/controller/admin/SearchLogController.php) — search 日志（list/export/delete）
 *   - [backend/app/controller/admin/LogController.php](../../../../../../backend/app/controller/admin/LogController.php) — balance/payment/operate/login 四类跨用户日志
 *
 * 所有跨用户日志共用筛选协议：
 *   user_id / username / start_time / end_time / page / page_size
 * 返回体：{list, total, page, page_size}。
 *
 * sort_by/sort_order 只 search 日志支持（服务层白名单）；其它 4 类固定 id desc。
 */
export namespace AdminLogApi {
  export interface SearchLog {
    id: number;
    log_no: string;
    user_id: number;
    keyword: string;
    result_count?: number;
    hit_source?: string;
    ip?: string;
    created_at: string;
  }

  export interface BalanceLog {
    id: number;
    user_id: number;
    username?: string;
    type: string; // recharge/consume/refund
    amount: string;
    balance_after: string;
    remark?: string;
    created_at: string;
  }

  export interface PaymentLog {
    id: number;
    order_no: string;
    trade_no?: string | null;
    user_id: number;
    username?: string;
    type: number; // 1 充值 / 2 套餐
    plan_id?: number | null;
    amount: string;
    pay_type: string;
    status: number; // 0 待支付 / 1 已支付 / 2 已取消
    created_at: string;
    paid_at?: string | null;
  }

  export interface OperateLog {
    id: number;
    user_id: number;
    username?: string;
    module: string;
    action: string;
    content?: string;
    ip?: string;
    created_at: string;
  }

  export interface LoginLog {
    id: number;
    user_id: number;
    username?: string;
    ip?: string;
    user_agent?: string;
    status: number; // 0 失败 / 1 成功
    created_at: string;
  }

  export interface BaseListParams {
    user_id?: null | number;
    username?: string;
    start_time?: string;
    end_time?: string;
    page?: number;
    page_size?: number;
  }

  export interface SearchListParams extends BaseListParams {
    keyword?: string;
    sort_by?: string;
    sort_order?: 'asc' | 'desc';
  }

  export interface BalanceListParams extends BaseListParams {
    type?: string; // recharge/consume/refund
  }

  export interface PaymentListParams extends BaseListParams {
    status?: null | number;
    pay_type?: string;
  }

  export interface OperateListParams extends BaseListParams {
    module?: string;
    action?: string;
  }

  export interface LoginListParams extends BaseListParams {
    status?: null | number;
    ip?: string;
  }

  export interface Page<T> {
    list: T[];
    total: number;
    page: number;
    page_size: number;
  }
}

// ---------- Search ----------
export async function listAdminSearchLogsApi(params?: AdminLogApi.SearchListParams) {
  return requestClient.get<AdminLogApi.Page<AdminLogApi.SearchLog>>(
    '/admin/log/search/list',
    { params },
  );
}

/**
 * CSV 导出：requestClient.download 自动设置 responseType:'blob'，绕过拦截器。
 */
export async function exportAdminSearchLogsApi(
  params?: Omit<AdminLogApi.SearchListParams, 'page' | 'page_size'>,
) {
  return requestClient.download<Blob>('/admin/log/search/export', { params });
}

/**
 * 批量硬删搜题日志。body.ids 为数字数组；任一 id 非法后端返 40001。
 */
export async function deleteAdminSearchLogsApi(ids: number[]) {
  return requestClient.delete<{ deleted: number }>('/admin/log/search/delete', {
    data: { ids },
  });
}

// ---------- Balance / Payment / Operate / Login ----------
export async function listAdminBalanceLogsApi(params?: AdminLogApi.BalanceListParams) {
  return requestClient.get<AdminLogApi.Page<AdminLogApi.BalanceLog>>(
    '/admin/log/balance',
    { params },
  );
}

export async function listAdminPaymentLogsApi(params?: AdminLogApi.PaymentListParams) {
  return requestClient.get<AdminLogApi.Page<AdminLogApi.PaymentLog>>(
    '/admin/log/payment',
    { params },
  );
}

export async function listAdminOperateLogsApi(params?: AdminLogApi.OperateListParams) {
  return requestClient.get<AdminLogApi.Page<AdminLogApi.OperateLog>>(
    '/admin/log/operate',
    { params },
  );
}

export async function listAdminLoginLogsApi(params?: AdminLogApi.LoginListParams) {
  return requestClient.get<AdminLogApi.Page<AdminLogApi.LoginLog>>(
    '/admin/log/login',
    { params },
  );
}
