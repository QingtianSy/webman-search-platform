<?php

namespace app\validate\admin;

use app\exception\BusinessException;
use support\ResponseCode;

class QuestionTagValidate
{
    public function create(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new BusinessException('标签名称不能为空', ResponseCode::PARAM_ERROR);
        }
        return [
            'name' => $name,
            'sort' => (int) ($data['sort'] ?? 0),
        ];
    }

    public function update(array $data): array
    {
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            throw new BusinessException('标签ID不能为空', ResponseCode::PARAM_ERROR);
        }
        return [
            'id' => $id,
            'name' => trim((string) ($data['name'] ?? '')),
            'sort' => (int) ($data['sort'] ?? 0),
        ];
    }
}
