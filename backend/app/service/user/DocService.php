<?php

namespace app\service\user;

use app\common\user\UserListBuilder;
use app\repository\mysql\DocArticleRepository;
use app\repository\mysql\DocCategoryRepository;
use app\repository\mysql\DocConfigRepository;

class DocService
{
    public function categories(array $query = []): array
    {
        $query += ['page' => 1, 'page_size' => 20];
        $page = (int) $query['page'];
        $pageSize = (int) $query['page_size'];
        $list = (new DocCategoryRepository())->all();
        return UserListBuilder::make($list, $page, $pageSize);
    }

    public function detail(string $slug): array
    {
        return (new DocArticleRepository())->findBySlug($slug);
    }

    public function config(): array
    {
        return (new DocConfigRepository())->get();
    }
}
