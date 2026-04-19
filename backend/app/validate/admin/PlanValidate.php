<?php

namespace app\validate\admin;

use app\exception\BusinessException;
use support\ResponseCode;

class PlanValidate
{
    public function create(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new BusinessException('套餐名称不能为空', ResponseCode::PARAM_ERROR);
        }
        $code = trim((string) ($data['code'] ?? ''));
        if ($code === '') {
            throw new BusinessException('套餐编码不能为空', ResponseCode::PARAM_ERROR);
        }
        $result = [
            'name' => $name,
            'code' => $code,
            'price' => (string) ($data['price'] ?? '0.00'),
            'duration' => (int) ($data['duration'] ?? 30),
            'quota' => (int) ($data['quota'] ?? 0),
            'is_unlimited' => (int) ($data['is_unlimited'] ?? 0),
            'sort' => (int) ($data['sort'] ?? 0),
            'status' => (int) ($data['status'] ?? 1),
        ];
        if (isset($data['features'])) {
            $features = $data['features'];
            if (is_string($features)) {
                $decoded = json_decode($features, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new BusinessException('features 格式错误，请传入合法 JSON', ResponseCode::PARAM_ERROR);
                }
                $features = $decoded;
            }
            $result['features'] = $features;
        }
        return $result;
    }

    public function update(array $data): array
    {
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            throw new BusinessException('套餐ID不能为空', ResponseCode::PARAM_ERROR);
        }
        $result = ['id' => $id];
        if (array_key_exists('name', $data)) {
            $result['name'] = trim((string) $data['name']);
        }
        if (array_key_exists('code', $data)) {
            $result['code'] = trim((string) $data['code']);
        }
        if (array_key_exists('price', $data)) {
            $result['price'] = (string) $data['price'];
        }
        if (array_key_exists('duration', $data)) {
            $result['duration'] = (int) $data['duration'];
        }
        if (array_key_exists('quota', $data)) {
            $result['quota'] = (int) $data['quota'];
        }
        if (array_key_exists('is_unlimited', $data)) {
            $result['is_unlimited'] = (int) $data['is_unlimited'];
        }
        if (array_key_exists('sort', $data)) {
            $result['sort'] = (int) $data['sort'];
        }
        if (array_key_exists('status', $data)) {
            $result['status'] = (int) $data['status'];
        }
        if (isset($data['features'])) {
            $features = $data['features'];
            if (is_string($features)) {
                $decoded = json_decode($features, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new BusinessException('features 格式错误，请传入合法 JSON', ResponseCode::PARAM_ERROR);
                }
                $features = $decoded;
            }
            $result['features'] = $features;
        }
        return $result;
    }
}
