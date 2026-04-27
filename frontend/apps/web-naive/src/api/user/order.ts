import { requestClient } from '#/api/request';

/**
 * 订单相关接口。
 * 对齐后端（部分 🆕 待补）：
 *   - POST /user/order/create             body: { order_type:'plan'|'recharge', plan_id?, amount?, pay_method }
 *                                         resp: { order_id, out_trade_no, pay_url?, qr_code_url? }
 *   - GET  /user/order/list?order_id=&status=&page=&page_size=
 *   - GET  /user/order/detail?order_id=   取单个订单（轮询支付状态用）
 *   - POST /user/order/continue?order_no= 🆕 复用订单号重新发起支付
 *   - POST /user/order/cancel?order_no=   🆕 取消待支付订单
 */
export namespace OrderApi {
  export type OrderType = 'plan' | 'recharge';
  export type OrderStatus =
    | 'cancelled'
    | 'expired'
    | 'failed'
    | 'pending'
    | 'refunded'
    | 'success';

  export interface CreateReq {
    order_type: OrderType;
    plan_id?: number;
    amount?: number | string;
    pay_method: string;
  }

  export interface CreateResp {
    order_id: number | string;
    out_trade_no: string;
    pay_url?: string;
    qr_code_url?: string;
    amount?: number | string;
    pay_method?: string;
  }

  export interface Order {
    id?: number | string;
    order_id?: number | string;
    out_trade_no: string;
    order_type?: OrderType;
    amount: number | string;
    pay_method: string;
    status: OrderStatus | number;
    fail_reason?: string | null;
    plan_id?: number | null;
    plan_name?: string | null;
    paid_at?: string | null;
    created_at: string;
  }

  export interface ListParams {
    order_id?: number | string;
    order_no?: string;
    status?: OrderStatus | number;
    pay_method?: string;
    date_from?: string;
    date_to?: string;
    page?: number;
    page_size?: number;
  }

  export interface ListResult {
    list: Order[];
    total: number;
    page: number;
    page_size: number;
  }
}

export async function createOrderApi(payload: OrderApi.CreateReq) {
  return requestClient.post<OrderApi.CreateResp>('/user/order/create', payload);
}

export async function listOrdersApi(params: OrderApi.ListParams = {}) {
  return requestClient.get<OrderApi.ListResult>('/user/order/list', { params });
}

export async function getOrderDetailApi(
  id: number | string,
): Promise<OrderApi.Order | null> {
  try {
    return await requestClient.get<OrderApi.Order>('/user/order/detail', {
      params: { order_id: id },
    });
  } catch {
    // 后端若只有 list 接口，这里 fallback 到 list 过滤
    const r = await listOrdersApi({ order_id: id, page_size: 1 });
    return r?.list?.[0] ?? null;
  }
}

export async function continueOrderApi(orderNo: string) {
  return requestClient.post<OrderApi.CreateResp>('/user/order/continue', null, {
    params: { order_no: orderNo },
  });
}

export async function cancelOrderApi(orderNo: string) {
  return requestClient.post<void>('/user/order/cancel', null, {
    params: { order_no: orderNo },
  });
}
