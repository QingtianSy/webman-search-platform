import { requestClient } from '#/api/request';

/** 管理端套餐管理。 */
export namespace AdminPlanApi {
  export interface PlanItem {
    id: number;
    name: string;
    price: number;
    daily_quota?: number;
    duration_days?: number;
    status: number;
    sort?: number;
    description?: string;
  }
}

export async function listAdminPlansApi() {
  return requestClient.get<AdminPlanApi.PlanItem[]>('/admin/plans');
}

export async function createPlanApi(data: Partial<AdminPlanApi.PlanItem>) {
  return requestClient.post<AdminPlanApi.PlanItem>('/admin/plans', data);
}

export async function updatePlanApi(id: number, data: Partial<AdminPlanApi.PlanItem>) {
  return requestClient.put<AdminPlanApi.PlanItem>(`/admin/plans/${id}`, data);
}

export async function deletePlanApi(id: number) {
  return requestClient.delete(`/admin/plans/${id}`);
}
