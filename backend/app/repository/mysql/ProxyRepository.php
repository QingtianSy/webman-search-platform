<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class ProxyRepository
{
    public function create(array $data): int
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return 0;
        }
        try {
            $stmt = $pdo->prepare('INSERT INTO proxies (name, protocol, host, port, username, password, country, country_code, province, city, latency_ms, status, created_at, updated_at) VALUES (:name, :protocol, :host, :port, :username, :password, :country, :country_code, :province, :city, :latency_ms, :status, NOW(), NOW())');
            $stmt->execute([
                'name' => $data['name'] ?? 'default',
                'protocol' => $data['protocol'],
                'host' => $data['host'],
                'port' => $data['port'],
                'username' => $data['username'] ?? null,
                'password' => $data['password'] ?? null,
                'country' => $data['country'] ?? null,
                'country_code' => $data['country_code'] ?? null,
                'province' => $data['province'] ?? null,
                'city' => $data['city'] ?? null,
                'latency_ms' => $data['latency_ms'] ?? null,
                'status' => $data['status'] ?? 0,
            ]);
            return (int) $pdo->lastInsertId();
        } catch (\PDOException $e) {
            error_log("[ProxyRepository] create failed: " . $e->getMessage());
            return 0;
        }
    }

    public function update(int $id, array $data): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $sets = [];
            $bind = ['id' => $id];
            foreach (['name', 'protocol', 'host', 'port', 'username', 'password', 'country', 'country_code', 'province', 'city'] as $field) {
                if (array_key_exists($field, $data)) {
                    $sets[] = "$field = :$field";
                    $bind[$field] = $data[$field];
                }
            }
            if (empty($sets)) {
                return false;
            }
            $sets[] = 'updated_at = NOW()';
            $sql = 'UPDATE proxies SET ' . implode(', ', $sets) . ' WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            return $stmt->execute($bind);
        } catch (\PDOException $e) {
            error_log("[ProxyRepository] update failed: " . $e->getMessage());
            return false;
        }
    }

    public function updateProbeResult(int $id, array $data): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $stmt = $pdo->prepare('UPDATE proxies SET country = :country, country_code = :country_code, province = :province, city = :city, latency_ms = :latency_ms, status = :status, updated_at = NOW() WHERE id = :id');
            return $stmt->execute([
                'id' => $id,
                'country' => $data['country'] ?? null,
                'country_code' => $data['country_code'] ?? null,
                'province' => $data['province'] ?? null,
                'city' => $data['city'] ?? null,
                'latency_ms' => $data['latency_ms'] ?? null,
                'status' => $data['status'] ?? 0,
            ]);
        } catch (\PDOException $e) {
            error_log("[ProxyRepository] updateProbeResult failed: " . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $stmt = $pdo->prepare('DELETE FROM proxies WHERE id = :id');
            return $stmt->execute(['id' => $id]);
        } catch (\PDOException $e) {
            error_log("[ProxyRepository] delete failed: " . $e->getMessage());
            return false;
        }
    }

    public function findById(int $id): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('SELECT * FROM proxies WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            return [];
        }
    }

    public function list(int $page = 1, int $pageSize = 20, array $filters = []): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return ['list' => [], 'total' => 0, 'page' => $page, 'page_size' => $pageSize];
        }
        try {
            $where = '1=1';
            $bind = [];
            if (!empty($filters['protocol'])) {
                $where .= ' AND protocol = :protocol';
                $bind['protocol'] = $filters['protocol'];
            }
            if (isset($filters['status']) && $filters['status'] !== '') {
                $where .= ' AND status = :status';
                $bind['status'] = (int) $filters['status'];
            }
            if (!empty($filters['keyword'])) {
                $where .= ' AND (host LIKE :kw OR name LIKE :kw2 OR city LIKE :kw3 OR province LIKE :kw4)';
                $bind['kw'] = '%' . $filters['keyword'] . '%';
                $bind['kw2'] = '%' . $filters['keyword'] . '%';
                $bind['kw3'] = '%' . $filters['keyword'] . '%';
                $bind['kw4'] = '%' . $filters['keyword'] . '%';
            }

            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM proxies WHERE $where");
            $countStmt->execute($bind);
            $total = (int) $countStmt->fetchColumn();

            $offset = ($page - 1) * $pageSize;
            $stmt = $pdo->prepare("SELECT id, name, protocol, host, port, username, password, country, country_code, province, city, latency_ms, status, used_count, last_used_at, created_at, updated_at FROM proxies WHERE $where ORDER BY id DESC LIMIT $pageSize OFFSET $offset");
            $stmt->execute($bind);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return ['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize];
        } catch (\PDOException $e) {
            error_log("[ProxyRepository] list failed: " . $e->getMessage());
            return ['list' => [], 'total' => 0, 'page' => $page, 'page_size' => $pageSize];
        }
    }

    public function all(): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->query('SELECT * FROM proxies WHERE status = 1 ORDER BY id ASC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            return [];
        }
    }

    public function findByLocation(string $province, string $city, int $cooldownMin = 5): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        $cooldownWhere = $cooldownMin > 0
            ? "AND (last_used_at IS NULL OR last_used_at < DATE_SUB(NOW(), INTERVAL {$cooldownMin} MINUTE))"
            : '';

        try {
            if ($city !== '') {
                $stmt = $pdo->prepare("SELECT * FROM proxies WHERE status = 1 AND province = :province AND city = :city $cooldownWhere ORDER BY used_count ASC LIMIT 1");
                $stmt->execute(['province' => $province, 'city' => $city]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    return $row;
                }
            }

            if ($province !== '') {
                $stmt = $pdo->prepare("SELECT * FROM proxies WHERE status = 1 AND province = :province $cooldownWhere ORDER BY used_count ASC LIMIT 1");
                $stmt->execute(['province' => $province]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    return $row;
                }
            }

            $stmt = $pdo->prepare("SELECT * FROM proxies WHERE status = 1 $cooldownWhere ORDER BY used_count ASC LIMIT 1");
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[ProxyRepository] findByLocation failed: " . $e->getMessage());
            return [];
        }
    }

    public function markUsed(int $id): void
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return;
        }
        try {
            $stmt = $pdo->prepare('UPDATE proxies SET used_count = used_count + 1, last_used_at = NOW() WHERE id = :id');
            $stmt->execute(['id' => $id]);
        } catch (\PDOException $e) {
            error_log("[ProxyRepository] markUsed failed: " . $e->getMessage());
        }
    }
}
