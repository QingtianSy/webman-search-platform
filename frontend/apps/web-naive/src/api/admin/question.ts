import { requestClient } from '#/api/request';

/**
 * 管理端题库管理。对应后端 admin/QuestionController。
 */
export namespace AdminQuestionApi {
  export interface QuestionOption {
    key: string;
    content: string;
  }

  export interface QuestionItem {
    question_id: number | string;
    stem: string;
    type: number;
    options?: QuestionOption[];
    answer_text?: string;
    analysis?: string;
    status: number;
    es_synced?: boolean;
    created_at?: string;
    updated_at?: string;
  }

  export interface QuestionListParams {
    keyword?: string;
    status?: number;
    type?: number;
    page?: number;
    page_size?: number;
  }
}

type PageResult<T> = { items: T[]; total: number };

export async function listQuestionsApi(params?: AdminQuestionApi.QuestionListParams) {
  return requestClient.get<PageResult<AdminQuestionApi.QuestionItem>>('/admin/questions', { params });
}

export async function getQuestionApi(id: string | number) {
  return requestClient.get<AdminQuestionApi.QuestionItem>(`/admin/questions/${id}`);
}

export async function createQuestionApi(data: Partial<AdminQuestionApi.QuestionItem>) {
  return requestClient.post<AdminQuestionApi.QuestionItem>('/admin/questions', data);
}

export async function updateQuestionApi(id: string | number, data: Partial<AdminQuestionApi.QuestionItem>) {
  return requestClient.put<AdminQuestionApi.QuestionItem>(`/admin/questions/${id}`, data);
}

export async function deleteQuestionApi(id: string | number) {
  return requestClient.delete(`/admin/questions/${id}`);
}

export async function reindexQuestionApi(id: string | number) {
  return requestClient.post(`/admin/questions/${id}/reindex`);
}
