<?php

namespace app\repository\mysql;

class SystemConfigRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/system_configs.json';
    }

    protected function allRows(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        return is_array($rows) ? $rows : [];
    }

    public function all(): array
    {
        return $this->allRows();
    }

    public function updateByKey(string $key, string $value): array
    {
        $rows = $this->allRows();
        foreach ($rows as &$row) {
            if (($row['config_key'] ?? '') === $key) {
                $row['config_value'] = $value;
                file_put_contents($this->file, json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                return $row;
            }
        }
        return [];
    }
}
