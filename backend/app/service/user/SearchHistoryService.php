<?php

namespace app\service\user;

use app\common\user\UserListBuilder;
use app\repository\mysql\SearchLogRepository;
use PDO;
use support\adapter\MySqlClient;

class SearchHistoryService
{
    public function getList(int $userId, array $query = []): array
    {
        $page = (int) ($query['page'] ?? 1);
        $pageSize = (int) ($query['page_size'] ?? 20);

        if (config('integration.log_source', 'mock') === 'real') {
            return $this->getListReal($userId, $page, $pageSize);
        }
        return $this->getListMock($userId, $page, $pageSize);
    }

    protected function getListMock(int $userId, int $page, int $pageSize): array
    {
        $file = base_path() . '/storage/logs/search_logs.jsonl';
        if (!is_file($file)) {
            return UserListBuilder::make([], $page, $pageSize);
        }
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $rows = [];
        foreach (array_reverse($lines ?: []) as $line) {
            $row = json_decode($line, true);
            if (is_array($row) && (int) ($row['user_id'] ?? 0) === $userId) {
                $rows[] = $row;
            }
        }
        return UserListBuilder::make($rows, $page, $pageSize);
    }

    protected function getListReal(int $userId, int $page, int $pageSize): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return UserListBuilder::make([], $page, $pageSize);
        }
        try {
            $offset = ($page - 1) * $pageSize;
            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM search_logs WHERE user_id = :user_id');
            $countStmt->execute(['user_id' => $userId]);
            $total = (int) $countStmt->fetchColumn();

            $stmt = $pdo->prepare('SELECT id, log_no, keyword, question_type, status, hit_count, source_type, consume_quota, cost_ms, created_at FROM search_logs WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
            $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue('limit', $pageSize, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return [
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
            ];
        } catch (\PDOException $e) {
            error_log("[SearchHistoryService] getListReal failed: " . $e->getMessage());
            return UserListBuilder::make([], $page, $pageSize);
        }
    }
}
