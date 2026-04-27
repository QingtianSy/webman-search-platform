import { requestClient } from '#/api/request';

/**
 * 用户 API Key。对齐后端 [backend/app/controller/user/ApiKeyController.php](../../../../../../backend/app/controller/user/ApiKeyController.php)：
 *   - GET    /user/api-key/list?page=&page_size=
 *   - GET    /user/api-key/detail?id=
 *   - POST   /user/api-key/create   body {app_name}
 *   - POST   /user/api-key/toggle   body {id, status}
 *   - DELETE /user/api-key/delete?id=
 *
 * api_secret 仅创建时返回一次，后端只存 bcrypt hash，前端必须立刻提示用户保存。
 */
export namespace ApiKeyApi {
  export interface ApiKeyItem {
    id: number;
    user_id: number;
    app_name: string;
    api_key: string;
    status: number;
    expire_at?: string | null;
    created_at: string;
    updated_at: string;
  }

  export interface CreateResult extends ApiKeyItem {
    /** 仅创建时返回一次 */
    api_secret: string;
  }

  export interface ListResult {
    list: ApiKeyItem[];
    total: number;
    page: number;
    page_size: number;
  }
}

export async function listApiKeysApi(params?: { page?: number; page_size?: number }) {
  return requestClient.get<ApiKeyApi.ListResult>('/user/api-key/list', { params });
}

export async function getApiKeyDetailApi(id: number) {
  return requestClient.get<ApiKeyApi.ApiKeyItem>('/user/api-key/detail', { params: { id } });
}

export async function createApiKeyApi(data: { app_name: string }) {
  return requestClient.post<ApiKeyApi.CreateResult>('/user/api-key/create', data);
}

export async function toggleApiKeyApi(id: number, status: number) {
  return requestClient.post('/user/api-key/toggle', { id, status });
}

export async function deleteApiKeyApi(id: number) {
  return requestClient.delete('/user/api-key/delete', { params: { id } });
}

/**
 * 设置默认 API Key（用于文档/控制台展示）。
 * 🆕 后端若未实现返 404，前端做本地存储兜底。
 */
export async function setDefaultApiKeyApi(id: number) {
  try {
    return await requestClient.post<void>('/user/api-key/set-default', null, {
      params: { id },
    });
  } catch {
    try {
      localStorage.setItem('default_api_key_id', String(id));
    } catch {
      // ignore
    }
  }
}

/**
 * 重新生成 api_secret（原 secret 作废）。
 * 🆕 后端若未实现，抛错由调用方兜底。
 */
export async function regenerateApiSecretApi(id: number) {
  return requestClient.post<ApiKeyApi.CreateResult>(
    '/user/api-key/regenerate',
    null,
    { params: { id } },
  );
}
