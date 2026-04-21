<?php

namespace app\controller\user;

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
}
