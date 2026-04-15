<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

/**
 * UserRepository
 *
 * 当前阶段：
 * - 默认从 storage/mock/users.json 读取用户
 *
 * 真接入阶段：
 * - 切换 AUTH_RBAC_SOURCE=real 后，改走真实 users 表查询
 */
class UserRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/users.json';
    }

    public function findByUsername(string $username): array
    {
        $source = config('integration.auth_rbac_source', 'mock');
        return $source === 'real'
            ? $this->findByUsernameReal($username)
            : $this->findByUsernameMock($username);
    }

    public function findById(int $id): array
    {
        $source = config('integration.auth_rbac_source', 'mock');
        return $source === 'real'
            ? $this->findByIdReal($id)
            : $this->findByIdMock($id);
    }

    protected function findByUsernameMock(string $username): array
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

    protected function findByIdMock(int $id): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        if (!is_array($rows)) {
            return [];
        }
        foreach ($rows as $row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                return $row;
            }
        }
        return [];
    }

    protected function findByUsernameReal(string $username): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        $stmt = $pdo->prepare('SELECT id, username, password_hash, nickname, avatar, mobile, email, status, last_login_ip, last_login_at, created_at, updated_at FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    protected function findByIdReal(int $id): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        $stmt = $pdo->prepare('SELECT id, username, password_hash, nickname, avatar, mobile, email, status, last_login_ip, last_login_at, created_at, updated_at FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}
