<?php

namespace app\validate\admin;

use app\exception\BusinessException;
use support\ResponseCode;

class MenuValidate
{
    public function create(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new BusinessException('菜单名称不能为空', ResponseCode::PARAM_ERROR);
        }
        $path = trim((string) ($data['path'] ?? ''));
        if ($path === '') {
            throw new BusinessException('菜单路径不能为空', ResponseCode::PARAM_ERROR);
        }
        $permissionCode = trim((string) ($data['permission_code'] ?? ''));
        if ($permissionCode === '') {
            throw new BusinessException('权限编码不能为空', ResponseCode::PARAM_ERROR);
        }
        return [
            'name' => $name,
            'path' => $path,
            'permission_code' => $permissionCode,
            'parent_id' => (int) ($data['parent_id'] ?? 0),
            'sort' => (int) ($data['sort'] ?? 0),
            'status' => (int) ($data['status'] ?? 1),
        ];
    }

    public function update(array $data): array
    {
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            throw new BusinessException('菜单ID不能为空', ResponseCode::PARAM_ERROR);
        }
        $result = ['id' => $id];
        if (array_key_exists('name', $data)) {
            $result['name'] = trim((string) $data['name']);
        }
        if (array_key_exists('path', $data)) {
            $result['path'] = trim((string) $data['path']);
        }
        if (array_key_exists('permission_code', $data)) {
            $result['permission_code'] = trim((string) $data['permission_code']);
        }
        if (array_key_exists('parent_id', $data)) {
            $result['parent_id'] = (int) $data['parent_id'];
        }
        if (array_key_exists('sort', $data)) {
            $result['sort'] = (int) $data['sort'];
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
