<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class UserRepository
{
    public function all(): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->query('SELECT * FROM users ORDER BY id DESC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[UserRepository] all failed: " . $e->getMessage());
            return [];
        }
    }

    public function findByUsername(string $username): ?array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return null;
        }
        try {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
            $stmt->execute(['username' => $username]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            error_log("[UserRepository] findByUsername failed: " . $e->getMessage());
            return null;
        }
    }

    public function findById(int $id): ?array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return null;
        }
        try {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            error_log("[UserRepository] findById failed: " . $e->getMessage());
            return null;
        }
    }

    public function updateProfile(int $id, array $data): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }
        $allowed = ['nickname', 'email', 'phone', 'avatar'];
        $sets = [];
        $bind = ['id' => $id];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = "{$field} = :{$field}";
                $bind[$field] = $data[$field];
            }
        }
        if (empty($sets)) {
            return false;
        }
        $sets[] = 'updated_at = NOW()';
        $sql = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id';
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($bind);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("[UserRepository] updateProfile failed: " . $e->getMessage());
            return false;
        }
    }
}
