<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class AnnouncementRepository
{
    public function latest(): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->query('SELECT id, title, content, type, status, publish_at, created_at, updated_at FROM announcements ORDER BY id DESC LIMIT 10000');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[AnnouncementRepository] latest failed: " . $e->getMessage());
            return [];
        }
    }

    public function create(array $data): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('INSERT INTO announcements (title, content, type, status, publish_at, created_at, updated_at) VALUES (:title, :content, :type, :status, :publish_at, NOW(), NOW())');
            $stmt->execute([
                'title' => $data['title'] ?? '',
                'content' => $data['content'] ?? '',
                'type' => $data['type'] ?? 'notice',
                'status' => $data['status'] ?? 1,
                'publish_at' => $data['publish_at'] ?? null,
            ]);
            return ['id' => (int) $pdo->lastInsertId()] + $data;
        } catch (\PDOException $e) {
            error_log("[AnnouncementRepository] create failed: " . $e->getMessage());
            return [];
        }
    }

    public function update(int $id, array $data): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('UPDATE announcements SET title = :title, content = :content, updated_at = NOW() WHERE id = :id');
            $stmt->execute([
                'id' => $id,
                'title' => $data['title'] ?? '',
                'content' => $data['content'] ?? '',
            ]);
            return ['id' => $id] + $data;
        } catch (\PDOException $e) {
            error_log("[AnnouncementRepository] update failed: " . $e->getMessage());
            return [];
        }
    }

    public function delete(int $id): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $stmt = $pdo->prepare('DELETE FROM announcements WHERE id = :id');
            $stmt->execute(['id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("[AnnouncementRepository] delete failed: " . $e->getMessage());
            return false;
        }
    }
}
