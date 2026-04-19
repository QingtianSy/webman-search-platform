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
            $stmt = $pdo->query('SELECT id, category_id, slug, title, summary, content_md, status, created_at, updated_at FROM docs_articles ORDER BY id DESC LIMIT 10000');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[DocArticleRepository] all failed: " . $e->getMessage());
            return [];
        }
    }

    public function countAll(): int
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return 0;
        }
        try {
            return (int) $pdo->query('SELECT COUNT(*) FROM docs_articles')->fetchColumn();
        } catch (\PDOException $e) {
            error_log("[DocArticleRepository] countAll failed: " . $e->getMessage());
            return 0;
        }
    }

    public function findPage(int $page, int $pageSize): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $offset = ($page - 1) * $pageSize;
            $stmt = $pdo->prepare('SELECT id, category_id, slug, title, summary, content_md, status, created_at, updated_at FROM docs_articles ORDER BY id DESC LIMIT :limit OFFSET :offset');
            $stmt->bindValue('limit', $pageSize, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[DocArticleRepository] findPage failed: " . $e->getMessage());
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
            $stmt = $pdo->prepare('SELECT id, category_id, slug, title, summary, content_md, status, created_at, updated_at FROM docs_articles WHERE slug = :slug AND status = 1 LIMIT 1');
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
            $check = $pdo->prepare('SELECT id FROM docs_articles WHERE id = :id');
            $check->execute(['id' => $id]);
            if (!$check->fetch()) {
                return [];
            }

            $sets = ['updated_at = NOW()'];
            $bind = ['id' => $id];
            $allowed = ['title', 'summary', 'content_md', 'status', 'category_id', 'slug'];
            foreach ($allowed as $field) {
                if (array_key_exists($field, $data)) {
                    $sets[] = "{$field} = :{$field}";
                    $bind[$field] = $data[$field];
                }
            }
            $sql = 'UPDATE docs_articles SET ' . implode(', ', $sets) . ' WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($bind);
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
