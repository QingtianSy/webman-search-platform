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
        $id = $this->id($data);
        $result = ['id' => $id];
        if (isset($data['stem'])) {
            $result['stem'] = trim((string) $data['stem']);
        }
        if (isset($data['answer_text'])) {
            $result['answer_text'] = trim((string) $data['answer_text']);
        }
        if (isset($data['options_text'])) {
            $result['options_text'] = trim((string) $data['options_text']);
        }
        if (isset($data['type_code'])) {
            $result['type_code'] = trim((string) $data['type_code']);
        }
        if (isset($data['type_name'])) {
            $result['type_name'] = trim((string) $data['type_name']);
        }
        if (isset($data['source_name'])) {
            $result['source_name'] = trim((string) $data['source_name']);
        }
        if (isset($data['course_name'])) {
            $result['course_name'] = trim((string) $data['course_name']);
        }
        if (isset($data['status'])) {
            $result['status'] = (int) $data['status'];
        }
        return $result;
    }

    public function create(array $data): array
    {
        $stem = trim((string) ($data['stem'] ?? ''));
        if ($stem === '') {
            throw new BusinessException('题干不能为空', ResponseCode::PARAM_ERROR);
        }
        return [
            'stem' => $stem,
            'answer_text' => trim((string) ($data['answer_text'] ?? '')),
            'options_text' => trim((string) ($data['options_text'] ?? '')),
            'type_code' => trim((string) ($data['type_code'] ?? 'single')),
            'type_name' => trim((string) ($data['type_name'] ?? '单选题')),
            'source_name' => trim((string) ($data['source_name'] ?? '')),
            'course_name' => trim((string) ($data['course_name'] ?? '')),
            'status' => (int) ($data['status'] ?? 1),
        ];
    }
}
