<?php

namespace app\service\admin;

use app\repository\mysql\DocArticleRepository;

class DocAdminService
{
    public function getList(array $query = []): array
    {
        $list = (new DocArticleRepository())->all();

        return [
            'list' => $list,
            'total' => count($list),
            'page' => 1,
            'page_size' => 20,
        ];
    }

    public function create(array $data): array
    {
        $row = (new DocArticleRepository())->create($data);
        return [
            'success' => true,
            'action' => 'create',
            'id' => $row['id'] ?? null,
            'data' => $row,
        ];
    }

    public function update(int $id, array $data): array
    {
        $row = (new DocArticleRepository())->update($id, $data);
        return [
            'success' => true,
            'action' => 'update',
            'id' => $id,
            'data' => $row,
        ];
    }

    public function delete(int $id): array
    {
        return [
            'success' => true,
            'action' => 'delete',
            'id' => $id,
        ];
    }
}
