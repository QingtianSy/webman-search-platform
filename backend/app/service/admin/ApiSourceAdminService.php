<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\repository\mysql\ApiSourceRepository;

class ApiSourceAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['keyword' => '', 'page' => 1, 'page_size' => 20];
        $keyword = trim((string) $query['keyword']);
        $page = (int) $query['page'];
        $pageSize = (int) $query['page_size'];

        $list = (new ApiSourceRepository())->all();
        if ($keyword !== '') {
            $list = array_values(array_filter($list, function ($row) use ($keyword) {
                return str_contains((string) ($row['name'] ?? ''), $keyword)
                    || str_contains((string) ($row['url'] ?? ''), $keyword);
            }));
        }
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
