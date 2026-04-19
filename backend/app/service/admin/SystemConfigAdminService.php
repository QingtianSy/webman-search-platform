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
        $row = (new SystemConfigRepository())->updateByKey($key, $value);
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
        if (in_array($row['config_key'] ?? '', self::SENSITIVE_KEYS, true)) {
            $val = $row['config_value'] ?? '';
            $row['config_value'] = strlen($val) > 8
                ? substr($val, 0, 4) . '****' . substr($val, -4)
                : '****';
        }
        return $row;
    }
}
