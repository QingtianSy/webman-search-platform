import { requestClient } from '#/api/request';

/**
 * 用户端日志四件套。对齐后端 [backend/app/controller/user/LogController.php](../../../../../../backend/app/controller/user/LogController.php)。
 *   - GET /user/log/balance?page=&page_size=   余额流水（充值/消费/退款）
 *   - GET /user/log/payment?page=&page_size=   支付记录（订单状态）
 *   - GET /user/log/login?page=&page_size=     登录记录（含 IP/UA）
 *   - GET /user/log/operate?page=&page_size=   操作审计（module/action/content）
 *
 * 注意：搜索日志在 api/user/search.ts 里（/user/search/logs），这里不重复。
 * 所有响应体统一 {list, total, page, page_size}（Pagination::format）。
 */
export namespace UserLogApi {
  export interface PageParams {
    page?: number;
    page_size?: number;
  }

  /** 日志筛选扩展参数（🆕 后端若未支持则忽略；无副作用）。 */
  export interface BalanceFilter extends PageParams {
    type?: number | string;
    order_no?: string;
    date_from?: string;
    date_to?: string;
  }

  export interface PaymentFilter extends PageParams {
    status?: number | string;
    pay_method?: string;
    order_no?: string;
    date_from?: string;
    date_to?: string;
  }

  export interface LoginFilter extends PageParams {
    status?: number | string;
    ip?: string;
    date_from?: string;
    date_to?: string;
  }

  export interface OperateFilter extends PageParams {
    module?: string;
    action?: string;
    status?: number | string;
    ip?: string;
    date_from?: string;
    date_to?: string;
  }

  export interface Page<T> {
    list: T[];
    total: number;
    page: number;
    page_size: number;
  }

  /**
   * 余额流水 balance_logs。
   * type 语义由后端枚举：1=充值 2=消费 3=退款 4=调整（具体以后端字典为准）。
   * amount 为正值；方向看 type。
   */
  export interface BalanceLog {
    id: number;
    user_id: number;
    type: number;
    amount: string | number;
    balance_after: string | number;
    remark?: string | null;
    created_at: string;
  }

  /** 支付记录 payment_logs。status: 0=未支付 1=已支付 2=失败 3=退款（具体以后端为准）。 */
  export interface PaymentLog {
    id: number;
    user_id: number;
    order_no: string;
    amount: string | number;
    pay_method: string;
    status: number;
    remark?: string | null;
    created_at: string;
  }

  /** 登录记录 login_logs。status: 1=成功 0=失败。 */
  export interface LoginLog {
    id: number;
    user_id: number;
    ip: string;
    user_agent?: string | null;
    status: number;
    created_at: string;
  }

  /** 操作审计 operate_logs。 */
  export interface OperateLog {
    id: number;
    user_id: number;
    module: string;
    action: string;
    content?: string | null;
    ip?: string | null;
    created_at: string;
  }
}

export async function listBalanceLogsApi(params?: UserLogApi.BalanceFilter) {
  return requestClient.get<UserLogApi.Page<UserLogApi.BalanceLog>>(
    '/user/log/balance',
    { params },
  );
}

export async function listPaymentLogsApi(params?: UserLogApi.PaymentFilter) {
  return requestClient.get<UserLogApi.Page<UserLogApi.PaymentLog>>(
    '/user/log/payment',
    { params },
  );
}

export async function listLoginLogsApi(params?: UserLogApi.LoginFilter) {
  return requestClient.get<UserLogApi.Page<UserLogApi.LoginLog>>(
    '/user/log/login',
    { params },
  );
}

export async function listOperateLogsApi(params?: UserLogApi.OperateFilter) {
  return requestClient.get<UserLogApi.Page<UserLogApi.OperateLog>>(
    '/user/log/operate',
    { params },
  );
}
