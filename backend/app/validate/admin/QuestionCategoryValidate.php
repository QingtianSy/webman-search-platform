<?php

namespace app\validate\admin;

use app\exception\BusinessException;
use support\ResponseCode;

class QuestionCategoryValidate
{
    public function create(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new BusinessException('分类名称不能为空', ResponseCode::PARAM_ERROR);
        }
        return [
            'name' => $name,
            'parent_id' => (int) ($data['parent_id'] ?? 0),
            'sort' => (int) ($data['sort'] ?? 0),
            'status' => (int) ($data['status'] ?? 1),
        ];
    }

    public function update(array $data): array
    {
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            throw new BusinessException('分类ID不能为空', ResponseCode::PARAM_ERROR);
        }
        return [
            'id' => $id,
            'name' => trim((string) ($data['name'] ?? '')),
            'parent_id' => (int) ($data['parent_id'] ?? 0),
            'sort' => (int) ($data['sort'] ?? 0),
            'status' => (int) ($data['status'] ?? 1),
        ];
    }
}
