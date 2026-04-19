<?php

namespace app\validate\user;

use app\exception\BusinessException;
use support\ResponseCode;

class ApiSourceValidate
{
    public function create(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new BusinessException('接口名称不能为空', ResponseCode::PARAM_ERROR);
        }
        $url = trim((string) ($data['url'] ?? ''));
        if ($url === '') {
            throw new BusinessException('接口地址不能为空', ResponseCode::PARAM_ERROR);
        }
        $method = strtoupper(trim((string) ($data['method'] ?? 'GET')));
        if (!in_array($method, ['GET', 'POST'], true)) {
            throw new BusinessException('请求方式仅支持 GET/POST', ResponseCode::PARAM_ERROR);
        }
        $keywordPosition = trim((string) ($data['keyword_position'] ?? 'url_param'));
        if (!in_array($keywordPosition, ['url_param', 'body'], true)) {
            $keywordPosition = 'url_param';
        }
        $typePosition = trim((string) ($data['type_position'] ?? 'url_param'));
        if (!in_array($typePosition, ['url_param', 'body'], true)) {
            $typePosition = 'url_param';
        }

        $headers = $data['headers'] ?? null;
        if ($headers !== null && $headers !== '') {
            if (is_string($headers)) {
                $decoded = json_decode($headers, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new BusinessException('请求头必须是有效的 JSON 格式', ResponseCode::PARAM_ERROR);
                }
                $headers = json_encode($decoded, JSON_UNESCAPED_UNICODE);
            }
        } else {
            $headers = null;
        }

        $extraConfig = $data['extra_config'] ?? null;
        if ($extraConfig !== null && $extraConfig !== '') {
            if (is_string($extraConfig)) {
                $decoded = json_decode($extraConfig, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new BusinessException('扩展配置必须是有效的 JSON 格式', ResponseCode::PARAM_ERROR);
                }
                $extraConfig = json_encode($decoded, JSON_UNESCAPED_UNICODE);
            }
        } else {
            $extraConfig = null;
        }

        return [
            'name' => $name,
            'method' => $method,
            'url' => $url,
            'keyword_param' => trim((string) ($data['keyword_param'] ?? 'q')) ?: 'q',
            'keyword_position' => $keywordPosition,
            'type_param' => trim((string) ($data['type_param'] ?? '')) ?: null,
            'type_position' => $typePosition,
            'option_delimiter' => trim((string) ($data['option_delimiter'] ?? '###')) ?: '###',
            'option_format' => trim((string) ($data['option_format'] ?? '')) ?: null,
            'headers' => $headers,
            'extra_config' => $extraConfig,
            'data_path' => trim((string) ($data['data_path'] ?? 'data')) ?: 'data',
            'success_code_field' => trim((string) ($data['success_code_field'] ?? 'code')) ?: 'code',
            'success_code_value' => trim((string) ($data['success_code_value'] ?? '1')) ?: '1',
            'timeout' => max(1, min(60, (int) ($data['timeout'] ?? 10))),
            'sort_order' => max(0, (int) ($data['sort_order'] ?? 0)),
            'status' => in_array((int) ($data['status'] ?? 1), [0, 1], true) ? (int) $data['status'] : 1,
            'remark' => trim((string) ($data['remark'] ?? '')) ?: null,
        ];
    }

    public function update(array $data): array
    {
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            throw new BusinessException('接口源ID不能为空', ResponseCode::PARAM_ERROR);
        }

        $result = ['id' => $id];

        if (isset($data['name'])) {
            $name = trim((string) $data['name']);
            if ($name === '') {
                throw new BusinessException('接口名称不能为空', ResponseCode::PARAM_ERROR);
            }
            $result['name'] = $name;
        }
        if (isset($data['url'])) {
            $url = trim((string) $data['url']);
            if ($url === '') {
                throw new BusinessException('接口地址不能为空', ResponseCode::PARAM_ERROR);
            }
            $result['url'] = $url;
        }
        if (isset($data['method'])) {
            $method = strtoupper(trim((string) $data['method']));
            if (!in_array($method, ['GET', 'POST'], true)) {
                throw new BusinessException('请求方式仅支持 GET/POST', ResponseCode::PARAM_ERROR);
            }
            $result['method'] = $method;
        }
        if (isset($data['keyword_param'])) {
            $result['keyword_param'] = trim((string) $data['keyword_param']) ?: 'q';
        }
        if (isset($data['keyword_position'])) {
            $v = trim((string) $data['keyword_position']);
            $result['keyword_position'] = in_array($v, ['url_param', 'body'], true) ? $v : 'url_param';
        }
        if (isset($data['type_param'])) {
            $result['type_param'] = trim((string) $data['type_param']) ?: null;
        }
        if (isset($data['type_position'])) {
            $v = trim((string) $data['type_position']);
            $result['type_position'] = in_array($v, ['url_param', 'body'], true) ? $v : 'url_param';
        }
        if (isset($data['option_delimiter'])) {
            $result['option_delimiter'] = trim((string) $data['option_delimiter']) ?: '###';
        }
        if (isset($data['option_format'])) {
            $result['option_format'] = trim((string) $data['option_format']) ?: null;
        }

        if (array_key_exists('headers', $data)) {
            $headers = $data['headers'];
            if ($headers !== null && $headers !== '') {
                if (is_string($headers)) {
                    $decoded = json_decode($headers, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new BusinessException('请求头必须是有效的 JSON 格式', ResponseCode::PARAM_ERROR);
                    }
                    $headers = json_encode($decoded, JSON_UNESCAPED_UNICODE);
                }
            } else {
                $headers = null;
            }
            $result['headers'] = $headers;
        }
        if (array_key_exists('extra_config', $data)) {
            $extraConfig = $data['extra_config'];
            if ($extraConfig !== null && $extraConfig !== '') {
                if (is_string($extraConfig)) {
                    $decoded = json_decode($extraConfig, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new BusinessException('扩展配置必须是有效的 JSON 格式', ResponseCode::PARAM_ERROR);
                    }
                    $extraConfig = json_encode($decoded, JSON_UNESCAPED_UNICODE);
                }
            } else {
                $extraConfig = null;
            }
            $result['extra_config'] = $extraConfig;
        }

        if (isset($data['data_path'])) {
            $result['data_path'] = trim((string) $data['data_path']) ?: 'data';
        }
        if (isset($data['success_code_field'])) {
            $result['success_code_field'] = trim((string) $data['success_code_field']) ?: 'code';
        }
        if (isset($data['success_code_value'])) {
            $result['success_code_value'] = trim((string) $data['success_code_value']) ?: '1';
        }
        if (isset($data['timeout'])) {
            $result['timeout'] = max(1, min(60, (int) $data['timeout']));
        }
        if (isset($data['sort_order'])) {
            $result['sort_order'] = max(0, (int) $data['sort_order']);
        }
        if (isset($data['status'])) {
            $result['status'] = in_array((int) $data['status'], [0, 1], true) ? (int) $data['status'] : 1;
        }
        if (isset($data['remark'])) {
            $result['remark'] = trim((string) $data['remark']) ?: null;
        }

        return $result;
    }

    public function id(array $data): int
    {
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            throw new BusinessException('接口源ID不能为空', ResponseCode::PARAM_ERROR);
        }
        return $id;
    }
}
