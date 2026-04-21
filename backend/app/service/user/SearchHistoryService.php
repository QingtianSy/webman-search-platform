<?php

namespace app\service\user;

use app\exception\BusinessException;
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
            // 搜索历史空列表与"真的没搜过"视觉上完全一样，会让用户看不到扣费记录、
            // 找不到 log_no，所以把故障暴露为 50001 让前端区分。
            throw new BusinessException('搜索历史暂不可用，请稍后重试', 50001);
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
            throw new BusinessException('搜索历史暂不可用，请稍后重试', 50001);
        }
    }
}
