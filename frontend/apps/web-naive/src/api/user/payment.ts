import { requestClient } from '#/api/request';

export namespace PaymentApi {
  export interface Method {
    code: string;
    name: string;
    icon?: string;
    enabled: boolean;
  }
}

const MOCK_METHODS: PaymentApi.Method[] = [
  { code: 'alipay', name: '支付宝支付', icon: 'logos:alipay', enabled: true },
  { code: 'wxpay', name: '微信支付', icon: 'logos:wechat', enabled: false },
  { code: 'qqpay', name: 'QQ支付', icon: 'ri:qq-fill', enabled: false },
];

export async function getPaymentMethodsApi(): Promise<PaymentApi.Method[]> {
  try {
    const result = await requestClient.get<
      { list: PaymentApi.Method[] } | PaymentApi.Method[]
    >('/user/payment/methods');
    if (Array.isArray(result)) return result;
    if (result && Array.isArray((result as any).list)) return (result as any).list;
    return MOCK_METHODS;
  } catch {
    return MOCK_METHODS;
  }
}
