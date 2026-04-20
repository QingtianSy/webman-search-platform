<?php

namespace app\controller\admin;

use app\repository\mysql\DocConfigRepository;
use app\service\admin\SystemConfigAdminService;
use support\ApiResponse;
use support\Request;

class DocConfigController
{
    // 只接受这些字段，其它字段一律丢弃，避免通过 doc_config 夹带任意 JSON 键写回 system_configs。
    private const WRITABLE_FIELDS = [
        'api_key',
        'multimodal_model',
        'text_model',
        'providers',
    ];

    public function list(Request $request)
    {
        $current = (new DocConfigRepository())->get();
        $current['api_key'] = self::maskApiKey((string) ($current['api_key'] ?? ''));
        return ApiResponse::success($current);
    }

    public function update(Request $request)
    {
        $input = $request->post();
        // 合并语义：读当前配置，按白名单字段覆写，再整体写回。
        // api_key 特殊处理：前端展示时是脱敏串，如果 admin 没填新值，input 里要么缺字段、要么还是 "****" 这类掩码，
        // 直接覆写会清掉真实密钥。只在 api_key 看起来是"新明文"时才替换。
        $merged = (new DocConfigRepository())->get();

        foreach (self::WRITABLE_FIELDS as $field) {
            if (!array_key_exists($field, $input)) {
                continue;
            }
            if ($field === 'api_key') {
                $newKey = trim((string) $input['api_key']);
                if ($newKey === '' || self::looksMasked($newKey)) {
                    continue;
                }
                $merged['api_key'] = $newKey;
                continue;
            }
            if ($field === 'providers') {
                if (!is_array($input['providers'])) {
                    return ApiResponse::error(40001, 'providers 须为数组');
                }
                $merged['providers'] = array_values($input['providers']);
                continue;
            }
            $merged[$field] = (string) $input[$field];
        }

        $json = json_encode($merged, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return ApiResponse::error(40001, '配置序列化失败');
        }

        // 复用 SystemConfigAdminService 做 value_type=json 校验 + 写库 + 返回脱敏行，
        // 这样回包里的 api_key 不会把 admin 刚写入的真实密钥再打一遍到响应体/日志里。
        $result = (new SystemConfigAdminService())->update('doc_config', $json);
        return ApiResponse::success($result, '文档配置更新成功');
    }

    private static function maskApiKey(string $val): string
    {
        if ($val === '' || $val === '未配置') {
            return $val;
        }
        return strlen($val) > 8
            ? substr($val, 0, 4) . '****' . substr($val, -4)
            : '****';
    }

    // 判掩码：SystemConfigAdminService::maskString 会产出 "head****tail" 或 "****"。
    // 只要包含 "****" 且与一次典型脱敏长度吻合就视为掩码，避免把它当新 key 存回去。
    private static function looksMasked(string $val): bool
    {
        return str_contains($val, '****');
    }
}
