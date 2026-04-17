<?php

namespace app\controller\user;

use app\common\user\UserListBuilder;
use app\common\user\UserQuery;
use app\repository\mysql\DocArticleRepository;
use app\repository\mysql\DocCategoryRepository;
use app\repository\mysql\DocConfigRepository;
use support\ApiResponse;
use support\Request;

class DocController
{
    public function categories(Request $request)
    {
        $query = UserQuery::parse($request->all());
        $list = (new DocCategoryRepository())->all();
        return ApiResponse::success(UserListBuilder::make($list, $query['page'], $query['page_size']));
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
