import { requestClient } from '#/api/request';

/**
 * 套餐相关接口。
 * 对齐后端（部分🆕待补）：
 *   - GET  /user/plan/current                         已有（见 WalletApi.CurrentPlan，本文件 re-export）
 *   - GET  /user/plan/list?type=&popular=&limit=      🆕 后端若 404 前端用空列表兜底
 *   - GET  /user/plan/popular?limit=                  🆕 取热门套餐（dashboard C 段）
 */
export namespace PlanApi {
  export type PlanType = 'exhaustive' | 'limited' | 'unlimited';

  export interface Plan {
    id: number;
    code?: string;
    name: string;
    description?: string;
    plan_type?: PlanType;
    is_unlimited?: number | boolean;
    price: number | string;
    original_price?: number | string | null;
    duration?: number | null; // 天
    quota?: number | null; // 次数 0=无限
    features?: string[] | null;
    is_recommended?: boolean | number;
    status?: number;
    sort?: number;
    sold_count?: number;
  }

  export interface ListParams {
    type?: PlanType;
    popular?: 0 | 1 | boolean;
    limit?: number;
    page?: number;
    page_size?: number;
  }

  export interface ListResult {
    list: Plan[];
    total?: number;
    page?: number;
    page_size?: number;
  }
}

/**
 * 从后端三元组 {is_unlimited,quota,duration} 派生 plan_type。
 * 后端返字段时优先使用；否则前端按规则推导。
 */
export function derivePlanType(plan: PlanApi.Plan): PlanApi.PlanType {
  if (plan.plan_type) return plan.plan_type;
  if (plan.is_unlimited) return 'unlimited';
  if (!plan.duration || Number(plan.duration) === 0) return 'exhaustive';
  return 'limited';
}

export async function getPlanListApi(params: PlanApi.ListParams = {}) {
  try {
    return await requestClient.get<PlanApi.ListResult>('/user/plan/list', {
      params,
    });
  } catch {
    return { list: [] } as PlanApi.ListResult;
  }
}

export async function getPopularPlansApi(limit = 3) {
  try {
    const r = await requestClient.get<PlanApi.ListResult>('/user/plan/list', {
      params: { popular: 1, limit },
    });
    return r?.list ?? [];
  } catch {
    return [];
  }
}

/**
 * 按 plan_type 拉取套餐列表。
 * 后端若不支持 type 过滤，前端拿全量再本地过滤。
 */
export async function getPlanListByTypeApi(type: PlanApi.PlanType) {
  try {
    const r = await requestClient.get<PlanApi.ListResult>('/user/plan/list', {
      params: { type, page_size: 100 },
    });
    const list = r?.list ?? [];
    // 本地兜底：后端若无 type 过滤，前端按 derive 筛一次
    return list.filter((p) => derivePlanType(p) === type);
  } catch {
    return [];
  }
}
