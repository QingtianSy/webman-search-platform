<?php

namespace app\service\admin;

use app\repository\mysql\ApiSourceRepository;
use support\Pagination;

class ApiSourceAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['page' => 1, 'page_size' => 20];
        $repo = new ApiSourceRepository();
        $total = $repo->countAll();
        $list = $repo->findPage((int) $query['page'], (int) $query['page_size']);
        return Pagination::format($list, $total, (int) $query['page'], (int) $query['page_size']);
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
