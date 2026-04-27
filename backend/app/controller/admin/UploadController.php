<?php

namespace app\controller\admin;

use app\repository\mysql\OperateLogRepository;
use app\service\admin\UploadAdminService;
use support\ApiResponse;
use support\Request;

/**
 * 通用文件上传（仅 admin 可用）。
 *   POST /admin/upload?scene=announcement|doc|question
 *   multipart/form-data，字段 file
 *   → { url, size, mime }
 *
 * scene 仅用于子目录分组，不做权限分片（前置 AdminAuthMiddleware）。
 * MIME 以 finfo 读魔数为准，白名单：image/jpeg|png|gif|webp。
 * 最大尺寸读 system_configs.upload_max_size，默认 5MB。
 */
class UploadController
{
    public function upload(Request $request)
    {
        $scene = (string) $request->get('scene', '');
        $file = $request->file('file');

        $result = (new UploadAdminService())->upload($file, $scene);

        $userId = (int) ($request->userId ?? 0);
        if ($userId > 0) {
            (new OperateLogRepository())->create([
                'user_id' => $userId,
                'module' => 'admin.upload',
                'action' => 'upload',
                'content' => "上传 [{$scene}] {$result['url']} ({$result['size']}B)",
                'ip' => $request->getRealIp(),
            ]);
        }

        return ApiResponse::success($result, '上传成功');
    }
}
