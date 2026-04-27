import { requestClient } from '#/api/request';

/**
 * 管理端 · 通用文件上传。对齐后端 [backend/app/controller/admin/UploadController.php](../../../../../../backend/app/controller/admin/UploadController.php)。
 *   - POST /admin/upload?scene=announcement|doc|question   multipart/form-data, field=file
 *   - 白名单 image/jpeg|png|gif|webp；大小上限读 system_configs.upload_max_size（默认 5MB）
 *   - 返回 { url, size, mime }；url 为相对路径 /uploads/...，由 nginx 静态托管
 *
 * 用法：
 *   ```ts
 *   const { url } = await uploadAdminFileApi(file, 'announcement');
 *   form.cover = url;
 *   ```
 */
export namespace AdminUploadApi {
  export type Scene = 'announcement' | 'doc' | 'question';

  export interface UploadResult {
    url: string;
    size: number;
    mime: string;
  }
}

export async function uploadAdminFileApi(
  file: File,
  scene: AdminUploadApi.Scene,
): Promise<AdminUploadApi.UploadResult> {
  const form = new FormData();
  form.append('file', file);
  return requestClient.post<AdminUploadApi.UploadResult>(
    '/admin/upload',
    form,
    {
      params: { scene },
      headers: { 'Content-Type': 'multipart/form-data' },
    },
  );
}
