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
        $result = ['id' => $id];
        if (array_key_exists('username', $data)) {
            $result['username'] = trim((string) $data['username']);
        }
        if (array_key_exists('nickname', $data)) {
            $result['nickname'] = trim((string) $data['nickname']);
        }
        if (array_key_exists('mobile', $data)) {
            $result['mobile'] = trim((string) $data['mobile']);
        }
        if (array_key_exists('email', $data)) {
            $result['email'] = trim((string) $data['email']);
        }
        if (array_key_exists('status', $data)) {
            $result['status'] = (int) $data['status'];
        }
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

    public function adjustBalance(array $data): array
    {
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            throw new BusinessException('用户ID不能为空', ResponseCode::PARAM_ERROR);
        }
        $amount = (float) ($data['amount'] ?? 0);
        if ($amount == 0) {
            throw new BusinessException('调整金额不能为 0', ResponseCode::PARAM_ERROR);
        }
        $remark = trim((string) ($data['remark'] ?? ''));
        if ($remark === '') {
            throw new BusinessException('调整备注不能为空', ResponseCode::PARAM_ERROR);
        }
        return ['id' => $id, 'amount' => $amount, 'remark' => $remark];
    }

    public function setSubscription(array $data): array
    {
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            throw new BusinessException('用户ID不能为空', ResponseCode::PARAM_ERROR);
        }
        // plan_id = null/0 表示清除套餐
        $planId = isset($data['plan_id']) ? (int) $data['plan_id'] : null;
        return ['id' => $id, 'plan_id' => $planId ?: null];
    }

    public function resetPassword(array $data): array
    {
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            throw new BusinessException('用户ID不能为空', ResponseCode::PARAM_ERROR);
        }
        $newPassword = (string) ($data['new_password'] ?? '');
        if (mb_strlen($newPassword) < 6) {
            throw new BusinessException('新密码长度不能小于6位', ResponseCode::PARAM_ERROR);
        }
        return ['id' => $id, 'new_password' => $newPassword];
    }
}
