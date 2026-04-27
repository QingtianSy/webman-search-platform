import { requestClient } from '#/api/request';

/**
 * 管理端 · 题目标签字典。对齐后端 [backend/app/controller/admin/QuestionTagController.php](../../../../../../backend/app/controller/admin/QuestionTagController.php)。
 *   - GET    /admin/question-tag/list?keyword=&page=&page_size=
 *   - POST   /admin/question-tag/create  body: {name*, sort?}
 *   - PUT    /admin/question-tag/update  body: {id*, ...partial}
 *   - DELETE /admin/question-tag/delete?id=
 */
export namespace AdminQuestionTagApi {
  export interface Tag {
    id: number;
    name: string;
    sort: number;
    created_at?: string;
    updated_at?: string;
  }

  export interface ListParams {
    keyword?: string;
    page?: number;
    page_size?: number;
  }

  export interface Page {
    list: Tag[];
    total: number;
    page: number;
    page_size: number;
  }

  export interface Payload {
    id?: number;
    name: string;
    sort?: number;
  }
}

export async function listQuestionTagsApi(
  params?: AdminQuestionTagApi.ListParams,
) {
  return requestClient.get<AdminQuestionTagApi.Page>(
    '/admin/question-tag/list',
    { params },
  );
}

export async function createQuestionTagApi(data: AdminQuestionTagApi.Payload) {
  return requestClient.post('/admin/question-tag/create', data);
}

export async function updateQuestionTagApi(
  data: AdminQuestionTagApi.Payload & { id: number },
) {
  return requestClient.put('/admin/question-tag/update', data);
}

export async function deleteQuestionTagApi(id: number) {
  return requestClient.delete('/admin/question-tag/delete', {
    params: { id },
  });
}
