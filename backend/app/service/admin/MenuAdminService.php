<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\repository\mysql\MenuRepository;

class MenuAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['keyword' => '', 'page' => 1, 'page_size' => 20];
        $keyword = trim((string) $query['keyword']);
        $page = (int) $query['page'];
        $pageSize = (int) $query['page_size'];

        $list = (new MenuRepository())->all();
        if ($keyword !== '') {
            $list = array_values(array_filter($list, function ($row) use ($keyword) {
                return str_contains((string) ($row['name'] ?? ''), $keyword)
                    || str_contains((string) ($row['path'] ?? ''), $keyword)
                    || str_contains((string) ($row['permission_code'] ?? ''), $keyword);
            }));
        }

        return AdminListBuilder::make($list, $page, $pageSize);
    }
}
