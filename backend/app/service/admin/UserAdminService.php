<?php

namespace app\service\admin;

use app\repository\mysql\UserRepository;
use support\Pagination;

class UserAdminService
{
    public function getList(): array
    {
        $list = (new UserRepository())->all();
        foreach ($list as &$row) {
            unset($row['password'], $row['password_hash'], $row['type']);
        }
        return Pagination::format($list, count($list), 1, 20);
    }
}
