<?php

namespace app\validate\admin;

use app\exception\BusinessException;
use support\ResponseCode;

class RoleValidate
{
    public function create(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new BusinessException('角色名称不能为空', ResponseCode::PARAM_ERROR);
        }
        $code = trim((string) ($data['code'] ?? ''));
        if ($code === '') {
            throw new BusinessException('角色编码不能为空', ResponseCode::PARAM_ERROR);
        }
        return [
            'name' => $name,
            'code' => $code,
            'sort' => (int) ($data['sort'] ?? 0),
            'status' => (int) ($data['status'] ?? 1),
        ];
    }

    public function update(array $data): array
    {
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            throw new BusinessException('角色ID不能为空', ResponseCode::PARAM_ERROR);
        }
        $result = [
            'id' => $id,
            'name' => trim((string) ($data['name'] ?? '')),
            'code' => trim((string) ($data['code'] ?? '')),
            'sort' => (int) ($data['sort'] ?? 0),
            'status' => (int) ($data['status'] ?? 1),
        ];
        if (isset($data['permission_ids']) && is_array($data['permission_ids'])) {
            $result['permission_ids'] = array_map('intval', $data['permission_ids']);
        }
        return $result;
    }

    public function assignPermissions(array $data): array
    {
        $roleId = (int) ($data['role_id'] ?? 0);
        if ($roleId <= 0) {
            throw new BusinessException('角色ID不能为空', ResponseCode::PARAM_ERROR);
        }
        $permissionIds = $data['permission_ids'] ?? [];
        if (!is_array($permissionIds)) {
            throw new BusinessException('权限ID列表格式错误', ResponseCode::PARAM_ERROR);
        }
        return [
            'role_id' => $roleId,
            'permission_ids' => array_map('intval', $permissionIds),
        ];
    }
}
