<?php

namespace app\validate\admin;

use app\exception\BusinessException;
use support\ResponseCode;

class PermissionValidate
{
    public function create(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new BusinessException('权限名称不能为空', ResponseCode::PARAM_ERROR);
        }
        $code = trim((string) ($data['code'] ?? ''));
        if ($code === '') {
            throw new BusinessException('权限编码不能为空', ResponseCode::PARAM_ERROR);
        }
        return [
            'name' => $name,
            'code' => $code,
            'type' => trim((string) ($data['type'] ?? 'action')),
            'status' => (int) ($data['status'] ?? 1),
        ];
    }

    public function update(array $data): array
    {
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            throw new BusinessException('权限ID不能为空', ResponseCode::PARAM_ERROR);
        }
        $result = ['id' => $id];
        if (array_key_exists('name', $data)) {
            $result['name'] = trim((string) $data['name']);
        }
        if (array_key_exists('code', $data)) {
            $result['code'] = trim((string) $data['code']);
        }
        if (array_key_exists('type', $data)) {
            $result['type'] = trim((string) $data['type']);
        }
        if (array_key_exists('status', $data)) {
            $result['status'] = (int) $data['status'];
        }
        if (count($result) <= 1) {
            throw new BusinessException('没有需要更新的字段', ResponseCode::PARAM_ERROR);
        }
        return $result;
    }
}
