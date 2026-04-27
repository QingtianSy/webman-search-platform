import { requestClient } from '#/api/request';

/**
 * 支付渠道公开接口。
 * 对齐后端（🆕 待补）：
 *   - GET /user/payment-methods   返已启用渠道 [{code,name,icon,enabled}]
 *
 * 后端未实现时前端走 mock 兜底（仅支付宝启用），不阻塞充值流程。
 */
export namespace PaymentApi {
  export interface Method {
    code: string;
    name: string;
    icon?: string;
    enabled: boolean;
  }
}

const MOCK_METHODS: PaymentApi.Method[] = [
  { code: 'alipay', name: '支付宝', icon: 'logos:alipay', enabled: true },
  { code: 'wechat', name: '微信支付', icon: 'logos:wechat', enabled: false },
  { code: 'qq', name: 'QQ 钱包', icon: 'ri:qq-fill', enabled: false },
];

export async function getPaymentMethodsApi(): Promise<PaymentApi.Method[]> {
  try {
    const r = await requestClient.get<{ list: PaymentApi.Method[] } | PaymentApi.Method[]>(
      '/user/payment-methods',
    );
    if (Array.isArray(r)) return r;
    if (r && Array.isArray((r as any).list)) return (r as any).list;
    return MOCK_METHODS;
  } catch {
    return MOCK_METHODS;
  }
}
