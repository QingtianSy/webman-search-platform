<?php

namespace app\repository\mysql;

class DocConfigRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/doc_config.json';
    }

    public function get(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $row = json_decode((string) file_get_contents($this->file), true);
        return is_array($row) ? $row : [];
    }
}
