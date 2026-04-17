<?php

namespace app\common\admin;

use app\exception\BusinessException;
use support\ResponseCode;

class AdminId
{
    public static function parse(array $data, string $key = 'id', string $label = 'ID'): int
    {
        $id = (int) ($data[$key] ?? 0);
        if ($id <= 0) {
            throw new BusinessException($label . '不能为空', ResponseCode::PARAM_ERROR);
        }
        return $id;
    }
}
