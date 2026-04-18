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
            'page' => max(1, (int) $request->input('page', 1)),
            'page_size' => max(1, min(100, (int) $request->input('page_size', 20))),
        ];
        return ApiResponse::success((new DocService())->categories($query));
    }

    public function detail(Request $request)
    {
        $slug = (string) $request->input('slug', '');
        return ApiResponse::success((new DocService())->detail($slug));
    }

    public function config(Request $request)
    {
        return ApiResponse::success((new DocService())->config());
    }
}
