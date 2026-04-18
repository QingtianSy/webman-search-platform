<?php

namespace app\validate\user;

use app\exception\BusinessException;
use support\ResponseCode;

class ApiKeyValidate
{
    public function id(array $data): int
    {
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            throw new BusinessException('API Key ID不能为空', ResponseCode::PARAM_ERROR);
        }
        return $id;
    }

    public function create(array $data): array
    {
        $appName = trim((string) ($data['app_name'] ?? '默认应用'));
        return ['app_name' => $appName === '' ? '默认应用' : $appName];
    }

    public function toggle(array $data): array
    {
        return [
            'id' => $this->id($data),
            'status' => (int) ($data['status'] ?? 1),
        ];
    }
}
