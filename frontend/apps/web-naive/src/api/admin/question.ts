import { requestClient } from '#/api/request';

/**
 * 管理端题库管理。对齐后端 [backend/app/controller/admin/QuestionController.php](../../../../../../backend/app/controller/admin/QuestionController.php)。
 *   - GET    /admin/question/list?keyword=&page=&page_size=&status=&start_time=&end_time=
 *   - GET    /admin/question/detail?id=
 *   - POST   /admin/question/create  body: {stem*, answer_text, options_text, type_code, type_name, source_name, course_name, status}
 *   - PUT    /admin/question/update  body: {id*, ...partial}
 *   - DELETE /admin/question/delete?id=
 *   - GET    /admin/question/export?...同 list filter    → CSV 下载
 *   - POST   /admin/question/reindex                     → 全量 ES 重建（耗时操作）
 *
 * 题目存 Mongo，question_id 是字符串形如 "QYYYYmmddHHiiss<hex>"。
 * options_text 是后端约定的"选项序列化文本"（如 "A|aaa###B|bbb"），页面表单按此串形态处理。
 */
export namespace AdminQuestionApi {
  export interface Question {
    id?: string;
    question_id: string;
    stem: string;
    answer_text?: string;
    options_text?: string;
    type_code?: string;
    type_name?: string;
    source_name?: string;
    course_name?: string;
    status: number;
    /** ES 最终一致标志；false 时提示"索引同步中" */
    es_synced?: boolean;
    created_at?: string;
    updated_at?: string;
  }

  export interface ListParams {
    keyword?: string;
    status?: number | string;
    page?: number;
    page_size?: number;
    sort?: string;
    order?: 'asc' | 'desc';
    start_time?: string;
    end_time?: string;
  }

  export interface Page {
    list: Question[];
    total: number;
    page: number;
    page_size: number;
  }

  export interface CreatePayload {
    stem: string;
    answer_text?: string;
    options_text?: string;
    type_code?: string;
    type_name?: string;
    source_name?: string;
    course_name?: string;
    status?: number;
  }

  export type UpdatePayload = Partial<CreatePayload> & { id: string };

  export interface ReindexResult {
    rebuilt?: boolean;
    count?: number;
    es_warning?: string;
    [k: string]: any;
  }

  export interface Stats {
    total: number;
    status_breakdown: {
      active: number;
      disabled: number;
    };
    dict: {
      category_count: number;
      type_count: number;
      source_count: number;
      tag_count: number;
    };
  }
}

export async function listQuestionsApi(params?: AdminQuestionApi.ListParams) {
  return requestClient.get<AdminQuestionApi.Page>('/admin/question/list', {
    params,
  });
}

export async function getQuestionApi(id: string) {
  return requestClient.get<AdminQuestionApi.Question>(
    '/admin/question/detail',
    { params: { id } },
  );
}

export async function createQuestionApi(data: AdminQuestionApi.CreatePayload) {
  return requestClient.post<AdminQuestionApi.Question>(
    '/admin/question/create',
    data,
  );
}

export async function updateQuestionApi(data: AdminQuestionApi.UpdatePayload) {
  return requestClient.put<AdminQuestionApi.Question>(
    '/admin/question/update',
    data,
  );
}

export async function deleteQuestionApi(id: string) {
  return requestClient.delete('/admin/question/delete', { params: { id } });
}

export async function reindexQuestionsApi(id?: string) {
  return requestClient.post<AdminQuestionApi.ReindexResult>(
    '/admin/question/reindex',
    id ? { id } : {},
  );
}

export async function statsQuestionsApi() {
  return requestClient.get<AdminQuestionApi.Stats>('/admin/question/stats');
}
