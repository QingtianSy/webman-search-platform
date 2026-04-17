<?php

namespace app\validate\admin;

use app\exception\BusinessException;
use support\ResponseCode;

class DocValidate
{
    public function create(array $data): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new BusinessException('文档标题不能为空', ResponseCode::PARAM_ERROR);
        }
        return [
            'category_id' => (int) ($data['category_id'] ?? 1),
            'slug' => (string) ($data['slug'] ?? 'new-doc'),
            'title' => $title,
            'summary' => (string) ($data['summary'] ?? ''),
            'content_md' => (string) ($data['content_md'] ?? ''),
            'status' => 1,
        ];
    }

    public function update(array $data): array
    {
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            throw new BusinessException('文档ID不能为空', ResponseCode::PARAM_ERROR);
        }
        return [
            'id' => $id,
            'title' => (string) ($data['title'] ?? ''),
            'summary' => (string) ($data['summary'] ?? ''),
            'content_md' => (string) ($data['content_md'] ?? ''),
        ];
    }
}
