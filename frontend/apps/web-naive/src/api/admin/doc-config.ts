import { requestClient } from '#/api/request';

/**
 * 管理端 · 文档配置（全局 AI Key / Model / Provider）。
 * 对齐后端 [backend/app/controller/admin/DocConfigController.php](../../../../../../backend/app/controller/admin/DocConfigController.php)。
 *   - GET  /admin/doc-config/list
 *   - POST /admin/doc-config/update
 *
 * WRITABLE_FIELDS: api_key, multimodal_model, text_model, providers
 * api_key 在 list 返回时会被 mask（如 "XXXX****XXXX"）；update 若值含 **** 会被后端忽略
 */
export namespace AdminDocConfigApi {
  export interface Config {
    api_key?: string;
    multimodal_model?: string;
    text_model?: string;
    providers?: any[];
  }

  export interface UpdatePayload {
    api_key?: string;
    multimodal_model?: string;
    text_model?: string;
    providers?: any[];
  }
}

export async function listDocConfigApi() {
  return requestClient.get<AdminDocConfigApi.Config>(
    '/admin/doc-config/list',
  );
}

export async function updateDocConfigApi(data: AdminDocConfigApi.UpdatePayload) {
  return requestClient.post<AdminDocConfigApi.Config>(
    '/admin/doc-config/update',
    data,
  );
}
