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
        $result = ['id' => $id];
        if (array_key_exists('name', $data)) {
            $result['name'] = trim((string) $data['name']);
        }
        if (array_key_exists('sort', $data)) {
            $result['sort'] = (int) $data['sort'];
        }
        if (count($result) <= 1) {
            throw new BusinessException('没有需要更新的字段', ResponseCode::PARAM_ERROR);
        }
        return $result;
    }
}
