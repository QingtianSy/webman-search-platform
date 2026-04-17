<?php

namespace app\validate\admin;

use app\exception\BusinessException;
use support\ResponseCode;

class ApiSourceValidate
{
    public function id(array $data): int
    {
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            throw new BusinessException('接口源ID不能为空', ResponseCode::PARAM_ERROR);
        }
        return $id;
    }
}
