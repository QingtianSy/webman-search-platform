<?php

namespace app\controller\open;

use app\service\quota\QuotaService;
use app\service\search\SearchService;
use support\ApiResponse;
use support\Request;

class SearchController
{
    public function query(Request $request)
    {
        $keyword = trim((string) $request->post('q', ''));
        $info = (string) $request->post('info', '');
        $split = (string) $request->post('split', '###');

        if ($keyword === '' || mb_strlen($keyword) < 2) {
            return ApiResponse::error(40001, '搜索关键词最少2个字符');
        }

        $userId = (int) ($request->apiKeyUserId ?? 0);
        $apiKeyId = (int) ($request->apiKeyId ?? 0);

        if ($userId <= 0) {
            return ApiResponse::error(40008, 'API Key 未关联用户');
        }

        $remainQuota = (new QuotaService())->getUserQuota($userId);
        if ($remainQuota <= 0) {
            return ApiResponse::error(40006, '额度不足');
        }

        $result = (new SearchService())->query($userId, $keyword, $info, $split, $apiKeyId);

        if (empty($result['list'])) {
            return ApiResponse::error(40004, '未找到匹配结果');
        }

        return ApiResponse::success([
            'log_no' => $result['log_no'] ?? '',
            'hit_count' => $result['hit_count'] ?? 0,
            'consume_quota' => $result['consume_quota'] ?? 0,
            'list' => array_map(fn ($item) => [
                'question_id' => $item['question_id'] ?? null,
                'stem' => $item['stem'] ?? '',
                'answer_text' => $item['answer_text'] ?? '',
                'type_name' => $item['type_name'] ?? '',
                'score' => $item['score'] ?? null,
            ], $result['list']),
        ]);
    }

    public function quotaDetail(Request $request)
    {
        $userId = (int) ($request->apiKeyUserId ?? 0);
        $quota = (new QuotaService())->getUserQuota($userId);
        return ApiResponse::success([
            'remain_quota' => $quota,
            'is_unlimited' => $quota >= 999999 ? 1 : 0,
        ]);
    }
}
