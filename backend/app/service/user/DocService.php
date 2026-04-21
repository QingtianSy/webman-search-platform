<?php

namespace app\service\user;

use app\common\user\UserListBuilder;
use app\exception\BusinessException;
use app\repository\mysql\DocArticleRepository;
use app\repository\mysql\DocCategoryRepository;
use app\repository\mysql\DocConfigRepository;

class DocService
{
    // 文档读接口（分类/详情/配置）在 DB 故障时之前都返回"空列表 / 40004"，把 DB 故障伪装成"没文档"。
    // 用户/运维都拿不到"后端挂了"的信号。改为 DB 故障抛 50001。
    // 真实"没文档 / 未开通配置"仍然返回空结构，由前端正常展示。

    public function categories(array $query = []): array
    {
        $query += ['page' => 1, 'page_size' => 20];
        $page = (int) $query['page'];
        $pageSize = (int) $query['page_size'];
        try {
            $list = (new DocCategoryRepository())->allStrict();
        } catch (\RuntimeException $e) {
            throw new BusinessException('文档服务暂不可用，请稍后重试', 50001);
        }
        return UserListBuilder::make($list, $page, $pageSize);
    }

    public function detail(string $slug): array
    {
        try {
            return (new DocArticleRepository())->findBySlugStrict($slug);
        } catch (\RuntimeException $e) {
            throw new BusinessException('文档服务暂不可用，请稍后重试', 50001);
        }
    }

    public function config(): array
    {
        try {
            $config = (new DocConfigRepository())->getStrict();
        } catch (\RuntimeException $e) {
            throw new BusinessException('文档服务暂不可用，请稍后重试', 50001);
        }
        unset($config['api_key']);
        return $config;
    }
}
