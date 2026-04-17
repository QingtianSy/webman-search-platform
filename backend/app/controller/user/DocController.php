<?php

namespace app\controller\user;

use app\repository\mysql\DocArticleRepository;
use app\repository\mysql\DocCategoryRepository;
use app\repository\mysql\DocConfigRepository;
use support\ApiResponse;
use support\Pagination;
use support\Request;

class DocController
{
    public function categories()
    {
        $list = (new DocCategoryRepository())->all();
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
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
