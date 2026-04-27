import { requestClient } from '#/api/request';

/**
 * 管理端 · 题型字典。对齐后端 [backend/app/controller/admin/QuestionTypeController.php](../../../../../../backend/app/controller/admin/QuestionTypeController.php)。
 *   - GET    /admin/question-type/list?keyword=&page=&page_size=
 *   - POST   /admin/question-type/create  body: {code*, name*, sort?, status?}
 *   - PUT    /admin/question-type/update  body: {id*, ...partial}
 *   - DELETE /admin/question-type/delete?id=
 *
 * code 唯一，用于题目 type_code 关联。
 */
export namespace AdminQuestionTypeApi {
  export interface Type {
    id: number;
    code: string;
    name: string;
    sort: number;
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
    list: Type[];
    total: number;
    page: number;
    page_size: number;
  }

  export interface Payload {
    id?: number;
    code: string;
    name: string;
    sort?: number;
    status?: number;
  }
}

export async function listQuestionTypesApi(
  params?: AdminQuestionTypeApi.ListParams,
) {
  return requestClient.get<AdminQuestionTypeApi.Page>(
    '/admin/question-type/list',
    { params },
  );
}

export async function createQuestionTypeApi(
  data: AdminQuestionTypeApi.Payload,
) {
  return requestClient.post('/admin/question-type/create', data);
}

export async function updateQuestionTypeApi(
  data: AdminQuestionTypeApi.Payload & { id: number },
) {
  return requestClient.put('/admin/question-type/update', data);
}

export async function deleteQuestionTypeApi(id: number) {
  return requestClient.delete('/admin/question-type/delete', {
    params: { id },
  });
}
