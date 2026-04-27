<?php

namespace app\controller\user;

use app\exception\BusinessException;
use app\repository\mysql\SystemConfigRepository;
use app\service\user\DocService;
use support\ApiResponse;
use support\Request;

class DocController
{
    public function categories(Request $request)
    {
        $query = [
            'page' => max(1, (int) $request->get('page', 1)),
            'page_size' => max(1, min(100, (int) $request->get('page_size', 20))),
        ];
        return ApiResponse::success((new DocService())->categories($query));
    }

    public function detail(Request $request)
    {
        $slug = (string) $request->get('slug', '');
        if ($slug === '') {
            return ApiResponse::error(40001, '参数错误');
        }
        // 服务层 DB 故障会抛 BusinessException(50001)；空数组只可能是真"不存在/未发布"。
        $result = (new DocService())->detail($slug);
        if (empty($result)) {
            return ApiResponse::error(40004, '文档不存在');
        }
        return ApiResponse::success($result);
    }

    public function config(Request $request)
    {
        return ApiResponse::success((new DocService())->config());
    }

    // 文档首卡元信息：接口地址 / 鉴权头 / RSA 公钥。
    // 来源 system_configs group=doc；缺键给空串，前端页面不至于白屏。
    public function meta(Request $request)
    {
        try {
            $rows = (new SystemConfigRepository())->getByGroupStrict('doc');
        } catch (\RuntimeException $e) {
            throw new BusinessException('配置服务暂不可用，请稍后重试', 50001);
        }
        $kv = [];
        foreach ($rows as $r) {
            $kv[$r['config_key']] = (string) ($r['config_value'] ?? '');
        }
        return ApiResponse::success([
            'api_base_url' => $kv['doc_api_base_url'] ?? ($kv['api_base_url'] ?? ''),
            'header_name' => $kv['doc_header_name'] ?? ($kv['header_name'] ?? 'Authorization'),
            'rsa_pub_key' => $kv['doc_rsa_pub_key'] ?? ($kv['rsa_pub_key'] ?? ''),
        ]);
    }
}
