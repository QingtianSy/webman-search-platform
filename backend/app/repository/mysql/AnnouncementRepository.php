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
            $stmt = $pdo->query('SELECT id, title, type, status, publish_at, created_at, updated_at FROM announcements WHERE status = 1 AND (publish_at IS NULL OR publish_at <= NOW()) ORDER BY id DESC LIMIT 10000');
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
            $sets = ['updated_at = NOW()'];
            $bind = ['id' => $id];
            $allowed = ['title', 'content', 'status', 'type', 'publish_at'];
            foreach ($allowed as $field) {
                if (array_key_exists($field, $data)) {
                    $sets[] = "{$field} = :{$field}";
                    $bind[$field] = $data[$field];
                }
            }
            $sql = 'UPDATE announcements SET ' . implode(', ', $sets) . ' WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($bind);
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
