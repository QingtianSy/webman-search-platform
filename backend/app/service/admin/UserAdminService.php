<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\repository\mysql\UserRepository;

class UserAdminService
{
    public function getList(): array
    {
        $list = (new UserRepository())->all();
        foreach ($list as &$row) {
            unset($row['password'], $row['password_hash'], $row['type']);
        }
        return AdminListBuilder::make($list);
    }
}
