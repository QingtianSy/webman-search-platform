import { requestClient } from '#/api/request';

/**
 * 用户端搜题 & 搜索历史。
 * 对齐后端 [backend/app/controller/user/SearchController.php](../../../../../../backend/app/controller/user/SearchController.php)：
 *   - POST /search/query       body: { q, info?, split? }
 *   - GET  /search/logs        query: { page?, page_size? }
 */
export namespace SearchApi {
  export interface QueryParams {
    q: string;
    /** 可选：用户附带的上下文信息（后端直通第三方 API，P1 前端先不暴露） */
    info?: string;
    /** 可选：分隔符，默认 '### '；P1 前端暂不暴露 */
    split?: string;
  }

  export interface QuestionOption {
    key?: string;
    label?: string;
    content: string;
  }

  export interface QuestionItem {
    question_id: string;
    stem: string;
    type_name?: string;
    answer_text?: string;
    analysis?: string;
    options?: QuestionOption[];
    keywords?: string[];
    score?: number | null;
    /**
     * ES 是否已同步（最终一致模型）；为 false 时前端应提示"索引同步中"
     * 见 memory/project_es_consistency.md
     */
    es_synced?: boolean;
    created_at?: string;
    [extra: string]: unknown;
  }

  export interface ApiResult {
    source_id: number | null;
    source_name: string;
    status: 'success' | 'error' | string;
    data: unknown;
  }

  export interface QueryResult {
    log_no: string;
    hit_count: number;
    consume_quota: number;
    list: QuestionItem[];
    api_results: ApiResult[];
    keyword: string;
    info: string;
    split: string;
  }

  export interface HistoryItem {
    id: number;
    log_no: string;
    keyword: string;
    question_type: null | number | string;
    status: number;
    hit_count: number;
    source_type: string;
    consume_quota: number;
    cost_ms: number;
    created_at: string;
  }

  export interface HistoryResult {
    list: HistoryItem[];
    total: number;
    page: number;
    page_size: number;
  }

  export interface HistoryParams {
    page?: number;
    page_size?: number;
    /** 关键词模糊匹配 */
    keyword?: string;
    /** 题型过滤（后端若未实现则忽略） */
    question_type?: number | string;
    /** 状态过滤：1=成功 0=失败 */
    status?: number | string;
  }
}

export async function searchQueryApi(params: SearchApi.QueryParams) {
  return requestClient.post<SearchApi.QueryResult>('/user/search/query', params);
}

export async function searchHistoryApi(params?: SearchApi.HistoryParams) {
  return requestClient.get<SearchApi.HistoryResult>('/user/search/logs', { params });
}
