<?php

namespace app\repository\mysql;

class DocArticleRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/docs_articles.json';
    }

    public function all(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        return is_array($rows) ? $rows : [];
    }

    public function findBySlug(string $slug): array
    {
        foreach ($this->all() as $row) {
            if (($row['slug'] ?? '') === $slug) {
                return $row;
            }
        }
        return [];
    }
}
