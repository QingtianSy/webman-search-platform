<?php

namespace app\validate\admin;

use app\exception\BusinessException;
use support\ResponseCode;

class QuestionTypeValidate
{
    public function create(array $data): array
    {
        $code = trim((string) ($data['code'] ?? ''));
        if ($code === '') {
            throw new BusinessException('题型编码不能为空', ResponseCode::PARAM_ERROR);
        }
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new BusinessException('题型名称不能为空', ResponseCode::PARAM_ERROR);
        }
        return [
            'code' => $code,
            'name' => $name,
            'sort' => (int) ($data['sort'] ?? 0),
            'status' => (int) ($data['status'] ?? 1),
        ];
    }

    public function update(array $data): array
    {
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            throw new BusinessException('题型ID不能为空', ResponseCode::PARAM_ERROR);
        }
        return [
            'id' => $id,
            'code' => trim((string) ($data['code'] ?? '')),
            'name' => trim((string) ($data['name'] ?? '')),
            'sort' => (int) ($data['sort'] ?? 0),
            'status' => (int) ($data['status'] ?? 1),
        ];
    }
}
