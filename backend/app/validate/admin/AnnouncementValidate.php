<?php

namespace app\validate\admin;

use app\exception\BusinessException;
use support\ResponseCode;

class AnnouncementValidate
{
    public function create(array $data): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new BusinessException('公告标题不能为空', ResponseCode::PARAM_ERROR);
        }
        return [
            'title' => $title,
            'content' => (string) ($data['content'] ?? ''),
            'type' => (string) ($data['type'] ?? 'notice'),
        ];
    }

    public function update(array $data): array
    {
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            throw new BusinessException('公告ID不能为空', ResponseCode::PARAM_ERROR);
        }
        return [
            'id' => $id,
            'title' => (string) ($data['title'] ?? ''),
            'content' => (string) ($data['content'] ?? ''),
        ];
    }
}
