<?php

namespace app\controller\admin;

use app\common\admin\AdminQuery;
use app\common\CsvExporter;
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
}
