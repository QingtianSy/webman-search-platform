<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class UserRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/users.json';
    }

    public function all(): array
    {
        return config('integration.auth_rbac_source', 'mock') === 'real'
            ? $this->allReal()
            : $this->allMock();
    }

    public function findByUsername(string $username): ?array
    {
        return config('integration.auth_rbac_source', 'mock') === 'real'
            ? $this->findByUsernameReal($username)
            : $this->findByUsernameMock($username);
    }

    public function findById(int $id): ?array
    {
        return config('integration.auth_rbac_source', 'mock') === 'real'
            ? $this->findByIdReal($id)
            : $this->findByIdMock($id);
    }

    protected function allMock(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        return is_array($rows) ? $rows : [];
    }

    protected function findByUsernameMock(string $username): ?array
    {
        foreach ($this->allMock() as $row) {
            if (($row['username'] ?? '') === $username) {
                return $row;
            }
        }
        return null;
    }

    protected function findByIdMock(int $id): ?array
    {
        foreach ($this->allMock() as $row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                return $row;
            }
        }
        return null;
    }

    protected function allReal(): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        $stmt = $pdo->query('SELECT * FROM users ORDER BY id DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    protected function findByUsernameReal(string $username): ?array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return null;
        }
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    protected function findByIdReal(int $id): ?array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return null;
        }
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
