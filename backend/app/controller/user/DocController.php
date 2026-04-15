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
    public function categories(): array
    {
        $list = (new DocCategoryRepository())->all();
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }

    public function detail(?Request $request = null): array
    {
        $request ??= new Request();
        $slug = (string) $request->input('slug', '');
        return ApiResponse::success((new DocArticleRepository())->findBySlug($slug));
    }

    public function config(): array
    {
        return ApiResponse::success((new DocConfigRepository())->get());
    }
}
