<?php

namespace app\validate\admin;

use app\exception\BusinessException;
use support\ResponseCode;

class UserValidate
{
    public function create(array $data): array
    {
        $username = trim((string) ($data['username'] ?? ''));
        if ($username === '') {
            throw new BusinessException('用户名不能为空', ResponseCode::PARAM_ERROR);
        }
        $password = (string) ($data['password'] ?? '');
        if (mb_strlen($password) < 6) {
            throw new BusinessException('密码长度不能小于6位', ResponseCode::PARAM_ERROR);
        }
        return [
            'username' => $username,
            'password' => $password,
            'nickname' => trim((string) ($data['nickname'] ?? '')),
            'mobile' => trim((string) ($data['mobile'] ?? '')),
            'email' => trim((string) ($data['email'] ?? '')),
            'status' => (int) ($data['status'] ?? 1),
        ];
    }

    public function update(array $data): array
    {
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            throw new BusinessException('用户ID不能为空', ResponseCode::PARAM_ERROR);
        }
        $result = [
            'id' => $id,
            'username' => trim((string) ($data['username'] ?? '')),
            'nickname' => trim((string) ($data['nickname'] ?? '')),
            'mobile' => trim((string) ($data['mobile'] ?? '')),
            'email' => trim((string) ($data['email'] ?? '')),
            'status' => (int) ($data['status'] ?? 1),
        ];
        $password = (string) ($data['password'] ?? '');
        if ($password !== '') {
            if (mb_strlen($password) < 6) {
                throw new BusinessException('密码长度不能小于6位', ResponseCode::PARAM_ERROR);
            }
            $result['password'] = $password;
        }
        if (isset($data['role_ids']) && is_array($data['role_ids'])) {
            $result['role_ids'] = array_map('intval', $data['role_ids']);
        }
        return $result;
    }

    public function assignRoles(array $data): array
    {
        $userId = (int) ($data['user_id'] ?? 0);
        if ($userId <= 0) {
            throw new BusinessException('用户ID不能为空', ResponseCode::PARAM_ERROR);
        }
        $roleIds = $data['role_ids'] ?? [];
        if (!is_array($roleIds)) {
            throw new BusinessException('角色ID列表格式错误', ResponseCode::PARAM_ERROR);
        }
        return [
            'user_id' => $userId,
            'role_ids' => array_map('intval', $roleIds),
        ];
    }
}
