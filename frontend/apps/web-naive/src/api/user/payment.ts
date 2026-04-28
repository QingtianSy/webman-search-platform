import { requestClient } from '#/api/request';

export namespace PaymentApi {
  export interface Method {
    code: string;
    name: string;
    icon?: string;
    enabled: boolean;
  }
}

const METHOD_META: Record<string, Omit<PaymentApi.Method, 'enabled'>> = {
  alipay: { code: 'alipay', name: '支付宝支付', icon: 'logos:alipay' },
  wxpay: { code: 'wxpay', name: '微信支付', icon: 'logos:wechat' },
  qqpay: { code: 'qqpay', name: 'QQ支付', icon: 'ri:qq-fill' },
};

const METHOD_ORDER = ['alipay', 'wxpay', 'qqpay'];

const METHOD_ALIASES: Record<string, string> = {
  qq: 'qqpay',
  qqpay: 'qqpay',
  wechat: 'wxpay',
  wx: 'wxpay',
  wxpay: 'wxpay',
  alipay: 'alipay',
};

function normalizeEnabled(value: unknown) {
  return value === true || value === 1 || value === '1' || value === 'true';
}

function normalizeMethod(method: PaymentApi.Method): null | PaymentApi.Method {
  const code = METHOD_ALIASES[String(method.code ?? '').toLowerCase()];
  const meta = code ? METHOD_META[code] : null;
  if (!meta) return null;
  return {
    ...meta,
    enabled: normalizeEnabled(method.enabled),
  };
}

function normalizeMethods(list: PaymentApi.Method[]) {
  const map = new Map<string, PaymentApi.Method>();
  for (const item of list) {
    const normalized = normalizeMethod(item);
    if (normalized) map.set(normalized.code, normalized);
  }
  return METHOD_ORDER
    .map((code) => map.get(code))
    .filter((item): item is PaymentApi.Method => Boolean(item));
}

export async function getPaymentMethodsApi(): Promise<PaymentApi.Method[]> {
  try {
    const result = await requestClient.get<
      { list: PaymentApi.Method[] } | PaymentApi.Method[]
    >('/user/payment/methods');
    if (Array.isArray(result)) return normalizeMethods(result);
    if (result && Array.isArray((result as any).list)) {
      return normalizeMethods((result as any).list);
    }
    return [];
  } catch {
    return [];
  }
}
