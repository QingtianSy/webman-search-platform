<?php

namespace app\validate\admin;

use app\exception\BusinessException;
use support\ResponseCode;

class QuestionValidate
{
    public function id(array $data): string
    {
        $id = trim((string) ($data['id'] ?? ''));
        if ($id === '') {
            throw new BusinessException('题目ID不能为空', ResponseCode::PARAM_ERROR);
        }
        return $id;
    }

    public function update(array $data): array
    {
        return [
            'id' => $this->id($data),
            'stem' => trim((string) ($data['stem'] ?? '')),
        ];
    }

    public function create(array $data): array
    {
        return [
            'stem' => trim((string) ($data['stem'] ?? '')),
        ];
    }
}
