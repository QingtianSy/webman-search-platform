<?php

namespace app\controller\admin;

use app\common\admin\AdminQuery;
use app\common\CsvExporter;
use app\service\admin\QuestionAdminService;
use app\service\question\QuestionIndexService;
use app\validate\admin\QuestionValidate;
use support\ApiResponse;
use support\Request;

class QuestionController
{
    public function index(Request $request)
    {
        $query = AdminQuery::parse($request->get());
        return ApiResponse::success((new QuestionAdminService())->getList($query), '题目列表');
    }

    public function detail(Request $request)
    {
        $id = (new QuestionValidate())->id($request->get());
        return ApiResponse::success((new QuestionAdminService())->detail($id));
    }

    public function create(Request $request)
    {
        $data = (new QuestionValidate())->create($request->post());
        return ApiResponse::success((new QuestionAdminService())->create($data), '题目创建成功');
    }

    public function update(Request $request)
    {
        $data = (new QuestionValidate())->update($request->post());
        $id = $data['id'];
        unset($data['id']);
        return ApiResponse::success((new QuestionAdminService())->update($id, $data), '题目更新成功');
    }

    public function delete(Request $request)
    {
        $id = (new QuestionValidate())->id($request->get());
        return ApiResponse::success((new QuestionAdminService())->delete($id), '题目删除成功');
    }

    public function export(Request $request)
    {
        $query = AdminQuery::parse($request->get());
        [$headers, $rows] = (new QuestionAdminService())->export($query);
        return CsvExporter::export('questions_' . date('Ymd_His') . '.csv', $headers, $rows);
    }

    public function reindex(Request $request)
    {
        $result = (new QuestionIndexService())->rebuild();
        if (empty($result['rebuilt'])) {
            return ApiResponse::error(50001, $result['error'] ?? 'ES索引重建失败');
        }
        $msg = isset($result['es_warning']) ? ('ES索引重建完成（部分失败）：' . $result['es_warning']) : 'ES索引重建完成';
        return ApiResponse::success($result, $msg);
    }
}
