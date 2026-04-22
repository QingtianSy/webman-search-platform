import { requestClient } from '#/api/request';

/**
 * 管理端套餐管理。对齐后端 [backend/app/controller/admin/PlanController.php](../../../../../../backend/app/controller/admin/PlanController.php)。
 *   - GET    /admin/plan/list?keyword=&page=&page_size=
 *   - POST   /admin/plan/create   body: {name*, code*, price, duration, quota, is_unlimited, sort, status, features?}
 *   - PUT    /admin/plan/update   body: {id*, ...partial}
 *   - DELETE /admin/plan/delete?id=
 *
 * - price 后端按字符串处理（DECIMAL），前端表单收发都用 string，避免 0.1+0.2 问题
 * - duration: 套餐有效天数；quota: 每日搜索配额（is_unlimited=1 时忽略）
 * - features: 任意 JSON（前端自由结构），后端会 json_encode 后入库；list/detail 取出时为字符串，需 JSON.parse
 */
export namespace AdminPlanApi {
  export interface Plan {
    id: number;
    name: string;
    code: string;
    price: string;
    duration: number;
    quota: number;
    is_unlimited: number;
    sort: number;
    status: number;
    /** 后端存为 JSON 字符串，列表里仍是字符串；前端按需 JSON.parse */
    features?: null | string;
    created_at?: string;
    updated_at?: string;
  }

  export interface ListParams {
    keyword?: string;
    page?: number;
    page_size?: number;
  }

  export interface Page {
    list: Plan[];
    total: number;
    page: number;
    page_size: number;
  }

  export interface CreatePayload {
    name: string;
    code: string;
    price?: string;
    duration?: number;
    quota?: number;
    is_unlimited?: number;
    sort?: number;
    status?: number;
    /** 数组对象，后端会自行 JSON 序列化 */
    features?: any;
  }

  export type UpdatePayload = Partial<CreatePayload> & { id: number };
}

export async function listAdminPlansApi(params?: AdminPlanApi.ListParams) {
  return requestClient.get<AdminPlanApi.Page>('/admin/plan/list', { params });
}

export async function createPlanApi(data: AdminPlanApi.CreatePayload) {
  return requestClient.post('/admin/plan/create', data);
}

export async function updatePlanApi(data: AdminPlanApi.UpdatePayload) {
  return requestClient.put('/admin/plan/update', data);
}

export async function deletePlanApi(id: number) {
  return requestClient.delete('/admin/plan/delete', { params: { id } });
}
