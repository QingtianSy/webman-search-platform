<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class DocArticleRepository
{
    public function all(): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->query('SELECT id, category_id, slug, title, summary, content_md, status, created_at, updated_at FROM docs_articles ORDER BY id DESC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[DocArticleRepository] all failed: " . $e->getMessage());
            return [];
        }
    }

    public function findBySlug(string $slug): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('SELECT id, category_id, slug, title, summary, content_md, status, created_at, updated_at FROM docs_articles WHERE slug = :slug LIMIT 1');
            $stmt->execute(['slug' => $slug]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[DocArticleRepository] findBySlug failed: " . $e->getMessage());
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
            $stmt = $pdo->prepare('INSERT INTO docs_articles (category_id, slug, title, summary, content_md, status, created_at, updated_at) VALUES (:category_id, :slug, :title, :summary, :content_md, :status, NOW(), NOW())');
            $stmt->execute([
                'category_id' => $data['category_id'] ?? 1,
                'slug' => $data['slug'] ?? 'new-doc',
                'title' => $data['title'] ?? '',
                'summary' => $data['summary'] ?? '',
                'content_md' => $data['content_md'] ?? '',
                'status' => $data['status'] ?? 1,
            ]);
            return ['id' => (int) $pdo->lastInsertId()] + $data;
        } catch (\PDOException $e) {
            error_log("[DocArticleRepository] create failed: " . $e->getMessage());
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
            $stmt = $pdo->prepare('UPDATE docs_articles SET title = :title, summary = :summary, content_md = :content_md, updated_at = NOW() WHERE id = :id');
            $stmt->execute([
                'id' => $id,
                'title' => $data['title'] ?? '',
                'summary' => $data['summary'] ?? '',
                'content_md' => $data['content_md'] ?? '',
            ]);
            return ['id' => $id] + $data;
        } catch (\PDOException $e) {
            error_log("[DocArticleRepository] update failed: " . $e->getMessage());
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
            $stmt = $pdo->prepare('DELETE FROM docs_articles WHERE id = :id');
            $stmt->execute(['id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("[DocArticleRepository] delete failed: " . $e->getMessage());
            return false;
        }
    }
}
