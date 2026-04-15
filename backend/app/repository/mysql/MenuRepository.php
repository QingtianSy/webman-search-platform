<?php

namespace app\repository\mysql;

/**
 * MenuRepository
 *
 * 当前阶段：
 * - 从 storage/mock/menus.json 读取菜单
 *
 * 真接入阶段：
 * - 替换为 MySQL menus 表查询
 * - 按 permission_code 过滤仍可保持现有逻辑不变
 */
class MenuRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/menus.json';
    }

    public function all(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        return is_array($rows) ? $rows : [];
    }
}
