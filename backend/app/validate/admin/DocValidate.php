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
        $slug = trim((string) ($data['slug'] ?? ''));
        if ($slug === '') {
            throw new BusinessException('文档 slug 不能为空', ResponseCode::PARAM_ERROR);
        }
        return [
            'category_id' => (int) ($data['category_id'] ?? 1),
            'slug' => $slug,
            'title' => $title,
            'summary' => (string) ($data['summary'] ?? ''),
            'content_md' => (string) ($data['content_md'] ?? ''),
            'status' => (int) ($data['status'] ?? 1),
        ];
    }

    public function update(array $data): array
    {
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            throw new BusinessException('文档ID不能为空', ResponseCode::PARAM_ERROR);
        }
        $result = ['id' => $id];
        if (array_key_exists('title', $data)) {
            $result['title'] = (string) $data['title'];
        }
        if (array_key_exists('summary', $data)) {
            $result['summary'] = (string) $data['summary'];
        }
        if (array_key_exists('content_md', $data)) {
            $result['content_md'] = (string) $data['content_md'];
        }
        if (isset($data['status'])) {
            $result['status'] = (int) $data['status'];
        }
        if (isset($data['category_id'])) {
            $result['category_id'] = (int) $data['category_id'];
        }
        if (isset($data['slug'])) {
            $slug = trim((string) $data['slug']);
            if ($slug === '') {
                throw new BusinessException('文档 slug 不能为空', ResponseCode::PARAM_ERROR);
            }
            $result['slug'] = $slug;
        }
        if (count($result) <= 1) {
            throw new BusinessException('没有需要更新的字段', ResponseCode::PARAM_ERROR);
        }
        return $result;
    }
}
