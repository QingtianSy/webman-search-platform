import { requestClient } from '#/api/request';

/**
 * 管理端 · 题库来源字典。对齐后端 [backend/app/controller/admin/QuestionSourceController.php](../../../../../../backend/app/controller/admin/QuestionSourceController.php)。
 *   - GET    /admin/question-source/list?keyword=&page=&page_size=
 *   - POST   /admin/question-source/create  body: {code*, name*, url?, status?}
 *   - PUT    /admin/question-source/update  body: {id*, ...partial}
 *   - DELETE /admin/question-source/delete?id=
 */
export namespace AdminQuestionSourceApi {
  export interface Source {
    id: number;
    code: string;
    name: string;
    url: string;
    status: number;
    created_at?: string;
    updated_at?: string;
  }

  export interface ListParams {
    keyword?: string;
    page?: number;
    page_size?: number;
  }

  export interface Page {
    list: Source[];
    total: number;
    page: number;
    page_size: number;
  }

  export interface Payload {
    id?: number;
    code: string;
    name: string;
    url?: string;
    status?: number;
  }
}

export async function listQuestionSourcesApi(
  params?: AdminQuestionSourceApi.ListParams,
) {
  return requestClient.get<AdminQuestionSourceApi.Page>(
    '/admin/question-source/list',
    { params },
  );
}

export async function createQuestionSourceApi(
  data: AdminQuestionSourceApi.Payload,
) {
  return requestClient.post('/admin/question-source/create', data);
}

export async function updateQuestionSourceApi(
  data: AdminQuestionSourceApi.Payload & { id: number },
) {
  return requestClient.put('/admin/question-source/update', data);
}

export async function deleteQuestionSourceApi(id: number) {
  return requestClient.delete('/admin/question-source/delete', {
    params: { id },
  });
}
