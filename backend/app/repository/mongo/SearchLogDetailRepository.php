<?php

namespace app\repository\mongo;

use support\adapter\MongoClient;

class SearchLogDetailRepository
{
    public function create(array $data): bool
    {
        $db = MongoClient::connection();
        if (!$db) {
            error_log("[SearchLogDetailRepository] MongoDB connection unavailable");
            return false;
        }
        try {
            $data['created_at'] = date('Y-m-d H:i:s');
            $db->selectCollection('search_log_details')->insertOne($data);
            return true;
        } catch (\Throwable $e) {
            error_log("[SearchLogDetailRepository] create failed: " . $e->getMessage());
            return false;
        }
    }
}
