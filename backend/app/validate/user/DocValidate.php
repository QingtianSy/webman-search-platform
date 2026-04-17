<?php

namespace app\validate\user;

use app\exception\BusinessException;
use support\ResponseCode;

class DocValidate
{
    public function slug(array $data): string
    {
        $slug = trim((string) ($data['slug'] ?? ''));
        if ($slug === '') {
            throw new BusinessException('文档标识不能为空', ResponseCode::PARAM_ERROR);
        }
        return $slug;
    }
}
