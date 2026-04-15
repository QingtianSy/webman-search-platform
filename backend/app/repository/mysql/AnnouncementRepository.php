<?php

namespace app\repository\mysql;

class AnnouncementRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/announcements.json';
    }

    protected function allRows(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        return is_array($rows) ? $rows : [];
    }

    public function latest(): array
    {
        return $this->allRows();
    }

    public function create(array $data): array
    {
        $rows = $this->allRows();
        $data['id'] = count($rows) + 1;
        $rows[] = $data;
        file_put_contents($this->file, json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return $data;
    }

    public function update(int $id, array $data): array
    {
        $rows = $this->allRows();
        foreach ($rows as &$row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                $row = array_merge($row, $data);
                file_put_contents($this->file, json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                return $row;
            }
        }
        return [];
    }
}
