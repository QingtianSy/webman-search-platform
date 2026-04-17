<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\common\admin\AdminStatusFilter;
use app\repository\mysql\RoleRepository;

class RoleAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['keyword' => '', 'status' => null, 'page' => 1, 'page_size' => 20];
        $keyword = trim((string) $query['keyword']);
        $page = (int) $query['page'];
        $pageSize = (int) $query['page_size'];

        $list = (new RoleRepository())->all();
        if ($keyword !== '') {
            $list = array_values(array_filter($list, function ($row) use ($keyword) {
                return str_contains((string) ($row['name'] ?? ''), $keyword)
                    || str_contains((string) ($row['code'] ?? ''), $keyword);
            }));
        }

        $list = AdminStatusFilter::apply($list, $query['status']);

        return AdminListBuilder::make($list, $page, $pageSize);
    }
}
