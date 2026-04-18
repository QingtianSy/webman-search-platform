<?php

namespace app\controller\user;

use app\repository\mysql\DocArticleRepository;
use app\repository\mysql\DocConfigRepository;
use support\ApiResponse;
use support\Request;

class DocController
{
    public function categories(Request $request)
    {
        $page = max(1, (int) $request->input('page', 1));
        $pageSize = max(1, min(100, (int) $request->input('page_size', 20)));
        
        $categories = (new \app\repository\mysql\DocCategoryRepository())->all();
        
        return ApiResponse::success([
            'list' => $categories,
            'total' => count($categories),
            'page' => $page,
            'page_size' => $pageSize
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
