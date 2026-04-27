<?php

namespace app\controller\admin;

use app\common\admin\AdminQuery;
use app\common\CsvExporter;
use app\exception\BusinessException;
use app\model\admin\SearchLog;
use app\repository\mysql\OperateLogRepository;
use app\service\admin\SearchLogAdminService;
use support\ApiResponse;
use support\Request;

class SearchLogController
{
    public function index(Request $request)
    {
        $query = AdminQuery::parse($request->get());
        return ApiResponse::success((new SearchLogAdminService())->getList($query));
    }

    public function export(Request $request)
    {
        $query = AdminQuery::parse($request->get());
        [$headers, $rows, $total, $limit] = (new SearchLogAdminService())->export($query);
        $suffix = $total > $limit ? "_partial_{$limit}_of_{$total}" : '';
        return CsvExporter::export('search_logs_' . date('Ymd_His') . $suffix . '.csv', $headers, $rows);
    }

    // 批量硬删搜题日志：接受 body/query 的 ids=1,2,3 或 ids[]=1&ids[]=2；
    // 任何 id 解析失败直接 40001，不做 "有些成功有些失败" 的静默兜底。
    public function delete(Request $request)
    {
        $adminId = (int) ($request->userId ?? 0);
        $raw = $request->input('ids', $request->get('ids', ''));
        $ids = [];
        if (is_array($raw)) {
            foreach ($raw as $v) {
                $id = (int) $v;
                if ($id > 0) $ids[] = $id;
            }
        } else {
            foreach (explode(',', (string) $raw) as $v) {
                $id = (int) trim($v);
                if ($id > 0) $ids[] = $id;
            }
        }
        $ids = array_values(array_unique($ids));
        if (empty($ids)) {
            return ApiResponse::error(40001, '请选择要删除的记录');
        }
        try {
            $affected = SearchLog::whereIn('id', $ids)->delete();
        } catch (\Throwable $e) {
            error_log('[SearchLogController] delete failed: ' . $e->getMessage());
            throw new BusinessException('搜题日志删除失败，请稍后重试', 50001);
        }
        (new OperateLogRepository())->create([
            'user_id' => $adminId, 'module' => 'admin_log',
            'action' => 'search_delete', 'content' => '删除搜题日志 ' . count($ids) . ' 条',
            'ip' => $request->getRealIp(),
        ]);
        return ApiResponse::success(['deleted' => $affected], '删除成功');
    }
}
