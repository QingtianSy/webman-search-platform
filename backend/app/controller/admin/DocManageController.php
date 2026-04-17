<?php

namespace app\controller\admin;

class DocManageController
{
    public function articles()
    {
        return json([
            'code' => 1,
            'msg' => 'success',
            'data' => [
                'list' => [
                    [
                        'id' => 1,
                        'title' => '接入说明',
                        'slug' => 'integration-guide',
                        'status' => 1
                    ]
                ],
                'total' => 1,
                'page' => 1,
                'page_size' => 20
            ],
            'request_id' => ''
        ]);
    }

    public function create()
    {
        return json(['code' => 1, 'msg' => 'success', 'data' => []]);
    }

    public function update()
    {
        return json(['code' => 1, 'msg' => 'success', 'data' => []]);
    }

    public function delete()
    {
        return json(['code' => 1, 'msg' => 'success', 'data' => ['deleted' => true]]);
    }
}
