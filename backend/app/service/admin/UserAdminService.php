<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\repository\mysql\UserRepository;

class UserAdminService
{
    public function getList(array $query): array
    {
        $list = (new UserRepository())->all();
        if ($query['keyword'] !== '') {
            $list = array_values(array_filter($list, function ($row) use ($query) {
                return str_contains((string) ($row['username'] ?? ''), $query['keyword'])
                    || str_contains((string) ($row['nickname'] ?? ''), $query['keyword']);
            }));
        }
        foreach ($list as &$row) {
            unset($row['password'], $row['password_hash'], $row['type']);
        }
        return AdminListBuilder::make($list, $query['page'], $query['page_size']);
    }
}
