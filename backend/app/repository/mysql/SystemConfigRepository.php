<?php

namespace app\repository\mysql;

class SystemConfigRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/system_configs.json';
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
