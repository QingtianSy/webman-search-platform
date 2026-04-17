<?php

namespace app\service\admin;

use app\repository\mysql\ApiSourceRepository;
use app\common\admin\AdminListBuilder;

class ApiSourceAdminService
{
    public function getList(): array
    {
        $list = (new ApiSourceRepository())->all();
        return AdminListBuilder::make($list);
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
