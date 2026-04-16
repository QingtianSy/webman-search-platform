<?php

namespace app\controller\admin;

use app\repository\mysql\DocArticleRepository;
use support\ApiResponse;
use support\Pagination;
use support\Request;

class DocManageController
{
    public function articles(): array
    {
        $list = (new DocArticleRepository())->all();
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }

    public function create(?Request $request = null): array
    {
        $request ??= new Request();
        $created = (new DocArticleRepository())->create([
            'category_id' => (int) $request->input('category_id', 1),
            'slug' => (string) $request->input('slug', 'new-doc'),
            'title' => (string) $request->input('title', '新文档'),
            'summary' => (string) $request->input('summary', ''),
            'content_md' => (string) $request->input('content_md', ''),
            'status' => 1,
        ]);
        return ApiResponse::success($created, '文档创建骨架已创建');
    }

    public function update(?Request $request = null): array
    {
        $request ??= new Request();
        $id = (int) $request->input('id', 0);
        $updated = (new DocArticleRepository())->update($id, [
            'title' => (string) $request->input('title', ''),
            'summary' => (string) $request->input('summary', ''),
            'content_md' => (string) $request->input('content_md', ''),
        ]);
        return ApiResponse::success($updated, '文档更新骨架已创建');
    }

    public function delete(?Request $request = null): array
    {
        $request ??= new Request();
        $id = (int) $request->input('id', 0);
        return ApiResponse::success(['deleted' => true, 'id' => $id], '文档删除骨架已创建');
    }
}
