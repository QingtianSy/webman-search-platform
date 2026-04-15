<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class DocArticleRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/docs_articles.json';
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
        return config('integration.docs_source', 'mock') === 'real'
            ? $this->allReal()
            : $this->allRows();
    }

    public function findBySlug(string $slug): array
    {
        return config('integration.docs_source', 'mock') === 'real'
            ? $this->findBySlugReal($slug)
            : $this->findBySlugMock($slug);
    }

    public function create(array $data): array
    {
        if (config('integration.docs_source', 'mock') === 'real') {
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
        if (config('integration.docs_source', 'mock') === 'real') {
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

    protected function findBySlugMock(string $slug): array
    {
        foreach ($this->allRows() as $row) {
            if (($row['slug'] ?? '') === $slug) {
                return $row;
            }
        }
        return [];
    }

    protected function allReal(): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        $stmt = $pdo->query('SELECT id, category_id, slug, title, summary, content_md, status, created_at, updated_at FROM docs_articles ORDER BY id DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    protected function findBySlugReal(string $slug): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        $stmt = $pdo->prepare('SELECT id, category_id, slug, title, summary, content_md, status, created_at, updated_at FROM docs_articles WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    protected function createReal(array $data): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
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
    }

    protected function updateReal(int $id, array $data): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        $stmt = $pdo->prepare('UPDATE docs_articles SET title = :title, summary = :summary, content_md = :content_md, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'title' => $data['title'] ?? '',
            'summary' => $data['summary'] ?? '',
            'content_md' => $data['content_md'] ?? '',
        ]);
        return ['id' => $id] + $data;
    }
}
