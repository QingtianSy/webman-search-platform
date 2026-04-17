<?php

namespace app\validate\admin;

use app\exception\BusinessException;
use support\ResponseCode;

class SystemConfigValidate
{
    public function update(array $data): array
    {
        $key = trim((string) ($data['config_key'] ?? ''));
        if ($key === '') {
            throw new BusinessException('配置键不能为空', ResponseCode::PARAM_ERROR);
        }
        return [
            'config_key' => $key,
            'config_value' => (string) ($data['config_value'] ?? ''),
        ];
    }
}
