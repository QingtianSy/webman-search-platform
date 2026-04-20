<?php

namespace app\service\admin;

use app\exception\BusinessException;
use app\repository\mysql\SystemConfigRepository;
use support\Pagination;

class SystemConfigAdminService
{
    private const SENSITIVE_KEYS = [
        'epay_key',
        'epay_merchant_private_key',
        'epay_platform_public_key',
    ];

    public function getList(array $query = []): array
    {
        $query += ['page' => 1, 'page_size' => 20];
        $repo = new SystemConfigRepository();
        $total = $repo->countAll();
        $list = $repo->findPage((int) $query['page'], (int) $query['page_size']);
        $list = array_map([self::class, 'maskRow'], $list);
        return Pagination::format($list, $total, (int) $query['page'], (int) $query['page_size']);
    }

    public function update(string $key, string $value): array
    {
        $repo = new SystemConfigRepository();
        $existing = $repo->findByKey($key);
        if (empty($existing)) {
            throw new BusinessException('配置项不存在', 40001);
        }
        self::validateValueType($value, $existing['value_type'] ?? 'string', $key);

        $row = $repo->updateByKey($key, $value);
        if (empty($row)) {
            throw new BusinessException('配置项不存在', 40001);
        }
        return [
            'success' => true,
            'action' => 'update',
            'id' => $row['id'] ?? null,
            'data' => self::maskRow($row),
        ];
    }

    private static function maskRow(array $row): array
    {
        $key = $row['config_key'] ?? '';
        if (in_array($key, self::SENSITIVE_KEYS, true)) {
            $row['config_value'] = self::maskString($row['config_value'] ?? '');
        } elseif ($key === 'doc_config') {
            $row['config_value'] = self::maskDocConfig($row['config_value'] ?? '');
        }
        return $row;
    }

    private static function maskString(string $val): string
    {
        return strlen($val) > 8
            ? substr($val, 0, 4) . '****' . substr($val, -4)
            : '****';
    }

    private static function maskDocConfig(string $json): string
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return '{"error":"invalid_json"}';
        }
        if (isset($data['api_key'])) {
            $data['api_key'] = self::maskString($data['api_key']);
        }
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    private static function validateValueType(string $value, string $type, string $key): void
    {
        switch ($type) {
            case 'number':
                if (!is_numeric($value)) {
                    throw new BusinessException("配置项 {$key} 须为数值", 40001);
                }
                break;
            case 'json':
                if (json_decode($value, true) === null && $value !== 'null') {
                    throw new BusinessException("配置项 {$key} 须为合法 JSON", 40001);
                }
                break;
            case 'boolean':
                if (!in_array($value, ['0', '1', 'true', 'false'], true)) {
                    throw new BusinessException("配置项 {$key} 须为布尔值", 40001);
                }
                break;
        }
    }
}
