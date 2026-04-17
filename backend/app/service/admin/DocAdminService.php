<?php

namespace app\service\admin;

use app\repository\mysql\DocArticleRepository;
use app\common\admin\AdminListBuilder;

class DocAdminService
{
    public function getList(): array
    {
        $list = (new DocArticleRepository())->all();
        return AdminListBuilder::make($list);
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
