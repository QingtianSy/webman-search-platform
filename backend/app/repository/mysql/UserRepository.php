<?php

namespace app\repository\mysql;

class UserRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/users.json';
    }

    public function findByUsername(string $username): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        if (!is_array($rows)) {
            return [];
        }
        foreach ($rows as $row) {
            if (($row['username'] ?? '') === $username) {
                return $row;
            }
        }
        return [];
    }
}
