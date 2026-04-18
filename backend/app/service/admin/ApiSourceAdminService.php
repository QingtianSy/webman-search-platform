<?php

namespace app\service\admin;

use app\repository\mysql\ApiSourceRepository;

class ApiSourceAdminService
{
    public function getList(array $query = []): array
    {
        $list = (new ApiSourceRepository())->all();

        return [
            'list' => $list,
            'total' => count($list),
            'page' => 1,
            'page_size' => 20,
        ];
    }

    public function detail(int $id): array
    {
        return (new ApiSourceRepository())->findById($id);
    }

    public function test(int $id): array
    {
        $row = (new ApiSourceRepository())->test($id);
        return [
            'success' => true,
            'action' => 'test',
            'id' => $id,
            'data' => $row,
        ];
    }
}
