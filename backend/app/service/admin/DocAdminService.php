<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\repository\mysql\DocArticleRepository;

class DocAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['keyword' => '', 'page' => 1, 'page_size' => 20];
        $keyword = trim((string) $query['keyword']);
        $page = (int) $query['page'];
        $pageSize = (int) $query['page_size'];

        $list = (new DocArticleRepository())->all();
        if ($keyword !== '') {
            $list = array_values(array_filter($list, function ($row) use ($keyword) {
                return str_contains((string) ($row['title'] ?? ''), $keyword)
                    || str_contains((string) ($row['slug'] ?? ''), $keyword);
            }));
        }
        return AdminListBuilder::make($list, $page, $pageSize);
    }

    public function create(array $data): array
    {
        return (new DocArticleRepository())->create($data);
    }

    public function update(int $id, array $data): array
    {
        return (new DocArticleRepository())->update($id, $data);
    }

    public function delete(int $id): array
    {
        return ['deleted' => true, 'id' => $id];
    }
}
