<?php

namespace app\service\user;

use app\common\user\UserListBuilder;
use PDO;
use support\adapter\MySqlClient;

class SearchHistoryService
{
    public function getList(int $userId, array $query = []): array
    {
        $page = (int) ($query['page'] ?? 1);
        $pageSize = (int) ($query['page_size'] ?? 20);

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
            error_log("[SearchHistoryService] getList failed: " . $e->getMessage());
            return UserListBuilder::make([], $page, $pageSize);
        }
    }
}
