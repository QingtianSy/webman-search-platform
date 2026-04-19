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
            'status' => (int) ($data['status'] ?? 1),
            'publish_at' => isset($data['publish_at']) && $data['publish_at'] !== '' ? (string) $data['publish_at'] : null,
        ];
    }

    public function update(array $data): array
    {
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            throw new BusinessException('公告ID不能为空', ResponseCode::PARAM_ERROR);
        }
        $result = ['id' => $id];
        if (array_key_exists('title', $data)) {
            $result['title'] = (string) $data['title'];
        }
        if (array_key_exists('content', $data)) {
            $result['content'] = (string) $data['content'];
        }
        if (isset($data['status'])) {
            $result['status'] = (int) $data['status'];
        }
        if (isset($data['type'])) {
            $result['type'] = (string) $data['type'];
        }
        if (array_key_exists('publish_at', $data)) {
            $result['publish_at'] = $data['publish_at'] !== '' && $data['publish_at'] !== null ? (string) $data['publish_at'] : null;
        }
        if (count($result) <= 1) {
            throw new BusinessException('没有需要更新的字段', ResponseCode::PARAM_ERROR);
        }
        return $result;
    }
}
