import { requestClient } from '#/api/request';

export namespace OrderApi {
  export type OrderType = 'plan' | 'recharge';
  export type OrderStatus = 'cancelled' | 'pending' | 'success';

  export interface CreateReq {
    order_type: OrderType;
    plan_id?: number;
    amount?: number | string;
    pay_method: string;
  }

  export interface CreateResp {
    id?: number | string | null;
    order_id: number | string | null;
    order_no: string;
    out_trade_no: string;
    pay_url?: string | null;
    qr_code_url?: string | null;
    amount?: number | string;
    pay_method?: string;
    pay_type?: string;
    order_type?: OrderType;
  }

  export interface Order {
    id?: number | string | null;
    order_id?: number | string | null;
    order_no: string;
    out_trade_no: string;
    order_type?: OrderType;
    type?: number;
    amount: number | string;
    pay_method: string;
    pay_type?: string;
    status: number;
    status_text?: OrderStatus;
    fail_reason?: null | string;
    plan_id?: null | number;
    plan_name?: null | string;
    remark?: null | string;
    paid_at?: null | string;
    created_at: string;
    pay_url?: null | string;
    qr_code_url?: null | string;
  }

  export interface ListParams {
    order_id?: number | string;
    order_no?: string;
    status?: number;
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

function normalizePayMethod(code: string) {
  const value = code.trim().toLowerCase();
  if (value === 'wechat' || value === 'wx') return 'wxpay';
  if (value === 'qq') return 'qqpay';
  return value;
}

function normalizeOrderType(type: unknown, orderType?: unknown): OrderApi.OrderType {
  if (orderType === 'plan' || orderType === 'recharge') {
    return orderType;
  }
  return Number(type) === 2 ? 'plan' : 'recharge';
}

function normalizeStatus(status: unknown): number {
  if (typeof status === 'number') return status;
  if (status === 'success') return 1;
  if (status === 'cancelled' || status === 'closed' || status === 'expired') {
    return 2;
  }
  return 0;
}

function normalizeOrder(raw: any): OrderApi.Order {
  const status = normalizeStatus(raw?.status);
  return {
    id: raw?.id ?? raw?.order_id ?? null,
    order_id: raw?.order_id ?? raw?.id ?? null,
    order_no: raw?.order_no ?? raw?.out_trade_no ?? '',
    out_trade_no: raw?.out_trade_no ?? raw?.order_no ?? '',
    order_type: normalizeOrderType(raw?.type, raw?.order_type),
    type: raw?.type !== undefined ? Number(raw.type) : undefined,
    amount: raw?.amount ?? '0',
    pay_method: raw?.pay_method ?? raw?.pay_type ?? '',
    pay_type: raw?.pay_type ?? raw?.pay_method ?? '',
    status,
    status_text:
      raw?.status_text ??
      (status === 1 ? 'success' : status === 2 ? 'cancelled' : 'pending'),
    fail_reason: raw?.fail_reason ?? null,
    plan_id: raw?.plan_id ?? null,
    plan_name: raw?.plan_name ?? null,
    remark: raw?.remark ?? null,
    paid_at: raw?.paid_at ?? null,
    created_at: raw?.created_at ?? '',
    pay_url: raw?.pay_url ?? null,
    qr_code_url: raw?.qr_code_url ?? null,
  };
}

function normalizeCreateResp(raw: any): OrderApi.CreateResp {
  const order = normalizeOrder(raw);
  return {
    id: order.id ?? null,
    order_id: order.order_id ?? null,
    order_no: order.order_no,
    out_trade_no: order.out_trade_no,
    pay_url: order.pay_url ?? null,
    qr_code_url: order.qr_code_url ?? null,
    amount: order.amount,
    pay_method: order.pay_method,
    pay_type: order.pay_type,
    order_type: order.order_type,
  };
}

export async function createOrderApi(payload: OrderApi.CreateReq) {
  const payMethod = normalizePayMethod(payload.pay_method);
  const body = {
    order_type: payload.order_type,
    type: payload.order_type === 'plan' ? 2 : 1,
    plan_id: payload.plan_id,
    amount: payload.amount,
    pay_method: payMethod,
    pay_type: payMethod,
  };
  const raw = await requestClient.post<any>('/user/order/create', body);
  return normalizeCreateResp(raw);
}

export async function listOrdersApi(params: OrderApi.ListParams = {}) {
  const raw = await requestClient.get<any>('/user/order/list', {
    params: {
      ...params,
      pay_method: params.pay_method ? normalizePayMethod(params.pay_method) : undefined,
    },
  });
  return {
    list: Array.isArray(raw?.list) ? raw.list.map((row: any) => normalizeOrder(row)) : [],
    total: raw?.total ?? 0,
    page: raw?.page ?? 1,
    page_size: raw?.page_size ?? 20,
  } satisfies OrderApi.ListResult;
}

export async function getOrderDetailApi(
  idOrOrderNo: number | string,
): Promise<OrderApi.Order | null> {
  const value = String(idOrOrderNo ?? '').trim();
  if (!value) return null;

  const isNumericId = /^\d+$/.test(value);
  const raw = await requestClient.get<any>('/user/order/detail', {
    params: isNumericId ? { order_id: value } : { order_no: value },
  });
  return normalizeOrder(raw);
}

export async function continueOrderApi(orderNo: string) {
  const raw = await requestClient.post<any>('/user/order/continue', {
    order_no: orderNo,
  });
  return normalizeCreateResp(raw);
}

export async function cancelOrderApi(orderNo: string) {
  return requestClient.post<void>('/user/order/cancel', {
    order_no: orderNo,
  });
}
