<?php

namespace app\controller\user;

use app\repository\mysql\DocArticleRepository;
use app\repository\mysql\DocConfigRepository;
use support\ApiResponse;
use support\Request;

class DocController
{
    public function categories()
    {
        return json([
            'code' => 1,
            'msg' => 'success',
            'data' => [
                'list' => [
                    [
                        'id' => 1,
                        'name' => '平台文档',
                        'slug' => 'platform-docs',
                        'sort' => 1,
                        'status' => 1
                    ]
                ],
                'total' => 1,
                'page' => 1,
                'page_size' => 20
            ],
            'request_id' => ''
        ]);
    }

    public function detail(Request $request)
    {
        $slug = (string) $request->input('slug', '');
        return ApiResponse::success((new DocArticleRepository())->findBySlug($slug));
    }

    public function config()
    {
        return ApiResponse::success((new DocConfigRepository())->get());
    }
}
