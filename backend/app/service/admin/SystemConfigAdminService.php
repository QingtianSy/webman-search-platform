<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\repository\mysql\SystemConfigRepository;

class SystemConfigAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['keyword' => '', 'page' => 1, 'page_size' => 20];
        $keyword = trim((string) $query['keyword']);
        $page = (int) $query['page'];
        $pageSize = (int) $query['page_size'];

        $list = (new SystemConfigRepository())->all();
        if ($keyword !== '') {
            $list = array_values(array_filter($list, function ($row) use ($keyword) {
                return str_contains((string) ($row['config_key'] ?? ''), $keyword)
                    || str_contains((string) ($row['config_value'] ?? ''), $keyword);
            }));
        }
        return AdminListBuilder::make($list, $page, $pageSize);
    }

    public function update(string $key, string $value): array
    {
        return (new SystemConfigRepository())->updateByKey($key, $value);
    }
}
