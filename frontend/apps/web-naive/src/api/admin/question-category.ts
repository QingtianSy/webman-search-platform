import { requestClient } from '#/api/request';

/**
 * 管理端 · 题目分类字典。对齐后端 [backend/app/controller/admin/QuestionCategoryController.php](../../../../../../backend/app/controller/admin/QuestionCategoryController.php)。
 *   - GET    /admin/question-category/list?keyword=&page=&page_size=
 *   - POST   /admin/question-category/create  body: {parent_id, name, sort?, status?}
 *   - PUT    /admin/question-category/update  body: {id, ...partial}
 *   - DELETE /admin/question-category/delete?id=
 */
export namespace AdminQuestionCategoryApi {
  export interface Category {
    id: number;
    parent_id: number;
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
    list: Category[];
    total: number;
    page: number;
    page_size: number;
  }

  export interface Payload {
    id?: number;
    parent_id?: number;
    name: string;
    sort?: number;
    status?: number;
  }
}

export async function listQuestionCategoriesApi(
  params?: AdminQuestionCategoryApi.ListParams,
) {
  return requestClient.get<AdminQuestionCategoryApi.Page>(
    '/admin/question-category/list',
    { params },
  );
}

export async function createQuestionCategoryApi(
  data: AdminQuestionCategoryApi.Payload,
) {
  return requestClient.post('/admin/question-category/create', data);
}

export async function updateQuestionCategoryApi(
  data: AdminQuestionCategoryApi.Payload & { id: number },
) {
  return requestClient.put('/admin/question-category/update', data);
}

export async function deleteQuestionCategoryApi(id: number) {
  return requestClient.delete('/admin/question-category/delete', {
    params: { id },
  });
}
