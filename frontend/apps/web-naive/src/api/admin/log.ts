import { requestClient } from '#/api/request';

/**
 * 管理端搜索日志查询 + CSV 导出。对齐后端 [backend/app/controller/admin/SearchLogController.php](../../../../../../backend/app/controller/admin/SearchLogController.php)。
 *   - GET /admin/log/search/list?keyword=&start_time=&end_time=&page=&page_size=&sort_by=&sort_order=
 *   - GET /admin/log/search/export?keyword=&start_time=&end_time=   返回 text/csv 附件，最多 50000 行
 *
 * - sort_by ∈ {id, log_no, keyword, created_at}，sort_order ∈ {asc, desc}，默认 created_at desc
 * - start_time/end_time 格式：YYYY-MM-DD HH:MM:SS
 */
export namespace AdminLogApi {
  export interface SearchLog {
    id: number;
    log_no: string;
    user_id: number;
    keyword: string;
    result_count?: number;
    hit_source?: string;
    ip?: string;
    created_at: string;
  }

  export interface ListParams {
    keyword?: string;
    start_time?: string;
    end_time?: string;
    sort_by?: string;
    sort_order?: 'asc' | 'desc';
    page?: number;
    page_size?: number;
  }

  export interface Page {
    list: SearchLog[];
    total: number;
    page: number;
    page_size: number;
  }
}

export async function listAdminSearchLogsApi(params?: AdminLogApi.ListParams) {
  return requestClient.get<AdminLogApi.Page>('/admin/log/search/list', {
    params,
  });
}

/**
 * CSV 导出：走 requestClient.download()，内部 responseType:'blob' + responseReturn:'body'，
 * 自然绕过 {code,msg,data} 响应拦截器。返回 Blob，调用方自行创建下载链接。
 */
export async function exportAdminSearchLogsApi(
  params?: Omit<AdminLogApi.ListParams, 'page' | 'page_size'>,
) {
  return requestClient.download<Blob>('/admin/log/search/export', { params });
}
