import { requestClient } from '#/api/request';

/**
 * 用户端四类日志：搜索 / 余额 / 支付 / 登录 / 操作。
 */
export namespace UserLogApi {
  export interface BaseLogItem {
    id: number;
    created_at: string;
  }

  export interface SearchLog extends BaseLogItem {
    keyword: string;
    result_count?: number;
    ip?: string;
  }

  export interface BalanceLog extends BaseLogItem {
    change: number;
    balance_after: number;
    reason?: string;
  }

  export interface PaymentLog extends BaseLogItem {
    order_no: string;
    amount: number;
    status: number;
    channel?: string;
  }

  export interface LoginLog extends BaseLogItem {
    ip: string;
    user_agent?: string;
    status: number;
  }
}

type PageResult<T> = { items: T[]; total: number };
type PageParams = { page?: number; page_size?: number };

export async function listUserSearchLogsApi(params?: PageParams) {
  return requestClient.get<PageResult<UserLogApi.SearchLog>>('/user/logs/search', { params });
}

export async function listUserBalanceLogsApi(params?: PageParams) {
  return requestClient.get<PageResult<UserLogApi.BalanceLog>>('/user/logs/balance', { params });
}

export async function listUserPaymentLogsApi(params?: PageParams) {
  return requestClient.get<PageResult<UserLogApi.PaymentLog>>('/user/logs/payment', { params });
}

export async function listUserLoginLogsApi(params?: PageParams) {
  return requestClient.get<PageResult<UserLogApi.LoginLog>>('/user/logs/login', { params });
}
