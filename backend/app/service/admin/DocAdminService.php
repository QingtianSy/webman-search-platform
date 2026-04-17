<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\repository\mysql\DocArticleRepository;

class DocAdminService
{
    public function getList(array $query = []): array
    {
        $page = $query['page'] ?? 1;
        $pageSize = $query['page_size'] ?? 20;
        $list = (new DocArticleRepository())->all();
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
