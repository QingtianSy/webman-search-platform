import { requestClient } from '#/api/request';

/**
 * 钱包 / 订阅（当前套餐）。
 * 对齐后端 [backend/app/controller/user/BillingController.php](../../../../../../backend/app/controller/user/BillingController.php)：
 *   - GET /user/wallet/detail
 *   - GET /user/plan/current
 * 未开通钱包 / 没活跃订阅时后端返回 {}（空对象），前端以空判断展示"未开通"。
 */
export namespace WalletApi {
  export interface Wallet {
    id?: number;
    user_id?: number;
    balance?: number | string;
    frozen_balance?: number | string;
    total_recharge?: number | string;
    total_consume?: number | string;
    created_at?: string;
    updated_at?: string;
  }

  export interface CurrentPlan {
    id?: number;
    user_id?: number;
    name?: string;
    is_unlimited?: number;
    remain_quota?: number;
    used_quota?: number;
    expire_at?: string | null;
    created_at?: string;
    updated_at?: string;
  }
}

export async function getWalletApi() {
  return requestClient.get<WalletApi.Wallet>('/user/wallet/detail');
}

export async function getCurrentPlanApi() {
  return requestClient.get<WalletApi.CurrentPlan>('/user/plan/current');
}
