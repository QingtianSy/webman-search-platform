<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class PaymentLogRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/payment_logs.json';
    }

    public function listByUserId(int $userId): array
    {
        return config('integration.log_source', 'mock') === 'real'
            ? $this->listByUserIdReal($userId)
            : $this->listByUserIdMock($userId);
    }

    protected function listByUserIdMock(int $userId): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        $rows = is_array($rows) ? $rows : [];
        return array_values(array_filter($rows, fn ($row) => (int) ($row['user_id'] ?? 0) === $userId));
    }

    protected function listByUserIdReal(int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('SELECT id, user_id, order_no, amount, pay_method, status, remark, created_at FROM payment_logs WHERE user_id = :user_id ORDER BY created_at DESC');
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[PaymentLogRepository] listByUserIdReal failed: " . $e->getMessage());
            return [];
        }
    }

    public function create(array $data): bool
    {
        return config('integration.log_source', 'mock') === 'real'
            ? $this->createReal($data)
            : $this->createMock($data);
    }

    protected function createMock(array $data): bool
    {
        if (!is_file($this->file)) {
            file_put_contents($this->file, '[]', LOCK_EX);
        }
        $rows = json_decode((string) file_get_contents($this->file), true) ?: [];
        $data['id'] = count($rows) + 1;
        $data['created_at'] = date('Y-m-d H:i:s');
        $rows[] = $data;
        return file_put_contents($this->file, json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX) !== false;
    }

    protected function createReal(array $data): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $stmt = $pdo->prepare('INSERT INTO payment_logs (user_id, order_no, amount, pay_method, status, remark, created_at) VALUES (:user_id, :order_no, :amount, :pay_method, :status, :remark, NOW())');
            return $stmt->execute([
                'user_id' => $data['user_id'] ?? 0,
                'order_no' => $data['order_no'] ?? '',
                'amount' => $data['amount'] ?? 0,
                'pay_method' => $data['pay_method'] ?? '',
                'status' => $data['status'] ?? 1,
                'remark' => $data['remark'] ?? '',
            ]);
        } catch (\PDOException $e) {
            error_log("[PaymentLogRepository] createReal failed: " . $e->getMessage());
            return false;
        }
    }
}
