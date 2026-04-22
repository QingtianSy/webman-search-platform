import { requestClient } from '#/api/request';

/**
 * 用户首页概览。对齐后端 [backend/app/service/user/DashboardService.php](../../../../../../backend/app/service/user/DashboardService.php)。
 *   - GET /user/dashboard/overview
 */
export namespace UserDashboardApi {
  export interface AnnouncementBrief {
    id: number;
    title: string;
    type: number;
    status: number;
    publish_at?: string | null;
    created_at?: string;
    updated_at?: string;
  }

  export interface CurrentPlanBrief {
    name: string;
    is_unlimited: number;
    remain_quota: number;
    expire_at?: string | null;
  }

  export interface Overview {
    balance: number | string;
    current_plan: CurrentPlanBrief;
    today_usage: number;
    total_usage: number;
    announcements: AnnouncementBrief[];
    user_id: number;
  }
}

export async function getUserDashboardApi() {
  return requestClient.get<UserDashboardApi.Overview>('/user/dashboard/overview');
}
