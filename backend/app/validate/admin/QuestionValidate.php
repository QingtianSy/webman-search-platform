<?php

namespace app\validate\admin;

use app\common\admin\AdminId;

class QuestionValidate
{
    public function id(array $data): int
    {
        return AdminId::parse($data, 'id', '题目ID');
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
