<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

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
            error_log("[AnnouncementRepository] Mock file not found: {$this->file}");
            return [];
        }
        
        $content = @file_get_contents($this->file);
        if ($content === false) {
            error_log("[AnnouncementRepository] Failed to read mock file: {$this->file}");
            return [];
        }
        
        $rows = json_decode($content, true);
        if (!is_array($rows)) {
            error_log("[AnnouncementRepository] Invalid JSON in mock file: {$this->file}");
            return [];
        }
        
        return $rows;
    }

    public function latest(): array
    {
        return config('integration.user_center_source', 'mock') === 'real'
            ? $this->latestReal()
            : $this->allRows();
    }

    public function create(array $data): array
    {
        if (config('integration.user_center_source', 'mock') === 'real') {
            return $this->createReal($data);
        }
        $rows = $this->allRows();
        $data['id'] = count($rows) + 1;
        $rows[] = $data;
        file_put_contents($this->file, json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return $data;
    }

    public function update(int $id, array $data): array
    {
        if (config('integration.user_center_source', 'mock') === 'real') {
            return $this->updateReal($id, $data);
        }
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

    protected function latestReal(): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            error_log("[AnnouncementRepository] Database connection failed");
            return [];
        }
        
        try {
            $stmt = $pdo->query('SELECT id, title, content, type, status, publish_at, created_at, updated_at FROM announcements ORDER BY id DESC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[AnnouncementRepository] Query failed: " . $e->getMessage());
            return [];
        }
    }

    protected function createReal(array $data): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        $stmt = $pdo->prepare('INSERT INTO announcements (title, content, type, status, publish_at, created_at, updated_at) VALUES (:title, :content, :type, :status, :publish_at, NOW(), NOW())');
        $stmt->execute([
            'title' => $data['title'] ?? '',
            'content' => $data['content'] ?? '',
            'type' => $data['type'] ?? 'notice',
            'status' => $data['status'] ?? 1,
            'publish_at' => $data['publish_at'] ?? null,
        ]);
        return ['id' => (int) $pdo->lastInsertId()] + $data;
    }

    protected function updateReal(int $id, array $data): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        $stmt = $pdo->prepare('UPDATE announcements SET title = :title, content = :content, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'title' => $data['title'] ?? '',
            'content' => $data['content'] ?? '',
        ]);
        return ['id' => $id] + $data;
    }
}
