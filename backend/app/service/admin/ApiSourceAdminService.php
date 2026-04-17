<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\repository\mysql\ApiSourceRepository;

class ApiSourceAdminService
{
    public function getList(array $query = []): array
    {
        $page = $query['page'] ?? 1;
        $pageSize = $query['page_size'] ?? 20;
        $list = (new ApiSourceRepository())->all();
        return AdminListBuilder::make($list, $page, $pageSize);
    }

    public function detail(int $id): array
    {
        return (new ApiSourceRepository())->findById($id);
    }

    public function test(int $id): array
    {
        return (new ApiSourceRepository())->test($id);
    }
}
