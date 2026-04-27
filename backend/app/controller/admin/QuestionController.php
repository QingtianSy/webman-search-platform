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
        $result = (new QuestionAdminService())->detail($id);
        if (empty($result)) {
            return ApiResponse::error(40004, '题目不存在');
        }
        return ApiResponse::success($result);
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
        [$headers, $rows, $total, $limit] = (new QuestionAdminService())->export($query);
        $suffix = $total > $limit ? "_partial_{$limit}_of_{$total}" : '';
        return CsvExporter::export('questions_' . date('Ymd_His') . $suffix . '.csv', $headers, $rows);
    }

    public function stats(Request $request)
    {
        return ApiResponse::success((new QuestionAdminService())->stats(), '题库统计');
    }

    public function reindex(Request $request)
    {
        // 可选参数 id（或 question_id）：若传则走单条同步，不传则整库重建。
        $id = $request->input('id', $request->input('question_id', ''));
        $id = is_string($id) ? trim($id) : '';

        if ($id !== '') {
            $ok = (new QuestionIndexService())->sync($id);
            if (!$ok) {
                return ApiResponse::error(40004, '题目不存在或同步失败', ['question_id' => $id]);
            }
            return ApiResponse::success([
                'question_id' => $id,
                'synced' => true,
            ], '单条同步完成');
        }

        $result = (new QuestionIndexService())->rebuild();
        if (empty($result['rebuilt'])) {
            return ApiResponse::error(50001, $result['error'] ?? 'ES索引重建失败');
        }
        $msg = isset($result['es_warning']) ? ('ES索引重建完成（部分失败）：' . $result['es_warning']) : 'ES索引重建完成';
        return ApiResponse::success($result, $msg);
    }
}
