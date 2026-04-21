# 后端性能优化审计报告

> 审计时间：2026-04-19
> 审计范围：`backend/` 全部 PHP 源码（约 83 个文件）
> 环境：Webman 2.2 + Workerman 5.1 + PHP 8.2 + MySQL + Redis + MongoDB + Elasticsearch

---

## 概要

共发现 **40+ 个性能优化点**，按影响面分为 6 个优先级。P0-P1 影响全局（每个请求），P2-P3 影响认证/后台，P4-P5 影响局部功能。

| 优先级 | 影响范围 | 优化点数 | 预估收益 |
|--------|----------|----------|----------|
| **P0** | 全局（每次 DB/Redis/Mongo 调用） | 3 | 每请求减少 1-3 次无意义网络往返 |
| **P1** | 搜索热路径（每次搜题） | 5 | 搜索延迟降低 30-50% |
| **P2** | 认证中间件（每个认证请求） | 4 | 冷缓存场景 DB 查询减半 |
| **P3** | Admin 后台 | 10+ | 消除 N+1 查询和内存分页 |
| **P4** | 代理/批量操作 | 5 | 批量操作从分钟级降到秒级 |
| **P5** | 服务器配置 | 3 | 减少内存占用、提升稳定性 |

---

## P0 — 连接健康检查：每次调用都 ping

### 问题

三个数据适配器在每次获取连接时都执行健康检查：

| 文件 | 行号 | 健康检查方式 | 调用频率 |
|------|------|-------------|----------|
| `support/adapter/MySqlClient.php` | 31-37 | `SELECT 1` | 每次 `pdo()` |
| `support/adapter/RedisClient.php` | 24-30 | `$redis->ping()` | 每次 `connection()` |
| `support/adapter/MongoClient.php` | 22-28 | `command(['ping'=>1])` | 每次 `connection()` |

在一个典型搜索请求中，`pdo()` 被调用 3-5 次，`connection()` 被调用 2-3 次。这意味着每次搜索请求额外执行 5-8 次无意义的网络往返。

### 修复方案

加时间戳节流：30 秒内最多 ping 一次。

```php
protected static int $lastPingAt = 0;

public static function pdo(): ?PDO
{
    if (self::$pdo !== null) {
        if (time() - self::$lastPingAt < 30) {
            return self::$pdo;
        }
        try {
            self::$pdo->query('SELECT 1');
            self::$lastPingAt = time();
            return self::$pdo;
        } catch (\Throwable) {
            self::$pdo = null;
        }
    }
    // ... 重建连接 ...
    self::$lastPingAt = time();
}
```

### MySqlClient PDO 属性补充

```php
PDO::ATTR_EMULATE_PREPARES => false,      // 真正的服务端预处理（安全 + MySQL 执行计划缓存）
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // 全局默认，无需每次指定
```

---

## P1 — 搜索热路径优化

### P1.1 ES + 第三方 API 串行 → 并行

**文件**: `app/service/search/SearchService.php:35-58`

**当前流程**（串行）：
```
ES搜索(~50ms) → MongoDB取详情(~30ms) → 第三方API搜索(~200-2000ms)
总延迟 = ES + Mongo + API = 280-2080ms
```

**优化后**（并行）：
```
[ES搜索 + MongoDB取详情] 同时 [第三方API搜索]
总延迟 = max(ES+Mongo, API) = 200-2000ms
```

ThirdPartySearchService 内部已使用 Guzzle `requestAsync` + `Pool`，只需在 SearchService 层把调用提前为 Promise，在 ES+Mongo 完成后 await 结果。

### P1.2 搜索日志写入阻塞返回

**文件**: `app/service/search/SearchService.php:81-101`

搜索完成后同步写 MySQL 日志 + MongoDB 详情日志（2 次 I/O），客户端必须等两次写入完成。

**修复**：用 `Timer::add(0, callback)` 将日志写入延迟到当前请求结束后执行（Workerman 事件循环下一 tick）。搜索结果先返回给客户端。

### P1.3 ES 返回全部字段，实际只用 question_id

**文件**: `app/repository/es/QuestionIndexRepository.php:21-31`

ES 返回完整 `_source`（stem、answer_text、analysis 等大文本），但 SearchService 只用 `question_id` + `_score`，详情从 MongoDB 取。

**修复**：加 `"_source": ["question_id"]`。

### P1.4 ES 搜索未过滤停用题目

**文件**: `app/repository/es/QuestionIndexRepository.php:22-29`

当前裸 `multi_match`，status=0（停用）的题目也会被搜到并返回。

**修复**：包裹 `bool` 查询：
```json
{
  "query": {
    "bool": {
      "must": { "multi_match": { ... } },
      "filter": { "term": { "status": 1 } }
    }
  }
}
```

`filter` 上下文不计分且可被 ES 缓存，对热门搜索有额外加速。

### P1.5 额度扣减 2 次 DB 往返 → 1 次

**文件**: `app/service/quota/QuotaService.php:91-105`

当前先 SELECT 检查 `is_unlimited`，再 UPDATE 扣减。对于非 unlimited 用户，每次搜索 2 次 MySQL 往返。

**修复**：合并为单条 SQL：
```sql
UPDATE user_subscriptions
SET remain_quota = IF(is_unlimited=1, remain_quota, remain_quota - :amt),
    used_quota   = IF(is_unlimited=1, used_quota,   used_quota + :amt),
    updated_at   = NOW()
WHERE user_id = :uid
  AND (expire_at IS NULL OR expire_at > NOW())
  AND (is_unlimited = 1 OR remain_quota >= :amt)
ORDER BY id DESC LIMIT 1
```

`rowCount() = 0` 时再用 `getUserQuota()` 区分"unlimited（成功）"和"余额不足（失败）"。同理改 `refund()`。

---

## P2 — 中间件优化

### P2.1 OpenApiAuthMiddleware 重复查询

**文件**: `app/middleware/OpenApiAuthMiddleware.php:18-21`

`verify()` 内部已调 `findByApiKey()`，但只返回 bool；中间件又查一次同样的 SQL。

**修复**：`ApiKeyService::verify()` 改为返回 `?array`（成功返回记录，失败返回 null）。影响文件：
- `app/service/open/ApiKeyService.php`
- `app/service/user/ApiKeyService.php`

### P2.2 Auth 中间件冷缓存时 3-4 次 DB 查询

**文件**: `app/middleware/AdminAuthMiddleware.php:57-69` / `UserAuthMiddleware.php:29-44`

当 Redis token 未命中（服务重启、Redis 宕机后恢复），触发 3 次 MySQL 查询。且查完后不回写 Redis，后续请求继续回源。

**修复**：回源查到角色后，写回 Redis（和登录时同样的 key + TTL），后续请求直接命中缓存。

### P2.3 ApiKeyService.detailById 全量扫描

**文件**: `app/service/user/ApiKeyService.php:17-24`

`detailById()` 加载用户全部 API key 然后循环查找。

**修复**：`ApiKeyRepository` 新增 `findById(int $userId, int $id)` 方法，`WHERE id = :id AND user_id = :user_id LIMIT 1`。

---

## P3 — Admin 后台优化

### P3.1 Dashboard 7 次查询 → 1 次 + Redis 缓存

**文件**: `app/service/admin/DashboardAdminService.php:17-27`

7 个独立 COUNT/SUM 合并为子查询：

```sql
SELECT
  (SELECT COUNT(*) FROM users) AS total_users,
  (SELECT COUNT(*) FROM users WHERE created_at >= CURDATE()) AS today_users,
  (SELECT COUNT(*) FROM search_logs) AS total_searches,
  (SELECT COUNT(*) FROM search_logs WHERE created_at >= CURDATE()) AS today_searches,
  (SELECT COALESCE(SUM(amount),0) FROM `order` WHERE status=1) AS total_order_amount,
  (SELECT COALESCE(SUM(amount),0) FROM `order` WHERE status=1 AND paid_at >= CURDATE()) AS today_order_amount,
  (SELECT COUNT(*) FROM questions) AS total_questions
```

再加 Redis 缓存（TTL 60 秒），MonitorService 复用缓存。

### P3.2 剩余内存分页服务 → DB 真分页

仍走 `Repository::all()` + `AdminListBuilder::make()` 内存分页的服务：

| 服务 | Repository 方法 |
|------|----------------|
| `ApiSourceAdminService.php:11` | `ApiSourceRepository::all()` |
| `DocAdminService.php:11` | `DocArticleRepository::all()` |
| `SystemConfigAdminService.php:13` | `SystemConfigRepository::all()` |
| `CollectAdminService.php:15` | `CollectTaskRepository::all()` |

用户端同理：
| 控制器 | 方法 |
|--------|------|
| `CollectController.php:17-29` | accounts/tasks |
| `ApiKeyController.php:17-20` | index |

**修复**：给每个 Repository 加 `countByFilters()` + `findPage($page, $pageSize)` 方法（SQL LIMIT/OFFSET），Service 层改用 `Pagination::format()`。

### P3.3 未加 LIMIT 的 all() 查询

给以下 Repository 方法加安全上限 `LIMIT 10000`：

| Repository 文件 | 方法 | 行号 |
|----------------|------|------|
| `AnnouncementRepository.php` | `latest()` | 17 |
| `CollectTaskRepository.php` | `all()` / `listByUserId()` | 17, 32 |
| `CollectAccountRepository.php` | `listByUserId()` | 17 |
| `ApiKeyRepository.php` | `listByUserId()` | 33 |
| `DocArticleRepository.php` | `all()` | 17 |
| `UserRepository.php` | `all()` | 17 |
| `SystemConfigRepository.php` | `all()` | 17 |
| `PermissionRepository.php` | `all()` | 17 |
| `QuestionRepository.php` | `findByTaskNo()` | 325 |

`UserRepository::all()` 额外改 `SELECT *` → 排除 password/password_hash 列。

### P3.4 AuthService 全量加载菜单后 PHP 过滤

**文件**: `app/service/auth/AuthService.php:50-52`

**修复**：`MenuRepository` 新增 `findByPermissionCodes(array $codes)` 方法。

---

## P4 — 代理/批量操作优化

### P4.1 probeAll / quickAdd 同步 HTTP 循环

**文件**: `app/service/proxy/ProxyService.php:89-211`

N 个代理 = N 次串行 HTTP（10s 超时/个），50 个代理最坏 500 秒。

**修复**：用 Guzzle `Pool`（concurrency=10）并发探测。quickAdd 先批量 INSERT → 并发 probe → 批量 UPDATE。

### P4.2 batchImport 逐条 INSERT

**文件**: `app/service/proxy/ProxyService.php:159-176`

**修复**：多行 `INSERT INTO ... VALUES (...), (...), (...)`。

### P4.3 syncAll 全量加载到内存

**文件**: `app/service/question/QuestionIndexService.php:19-36`

`findList([], 0)` 把所有题目一次性载入 PHP 数组再 `array_chunk`。

**修复**：改为分批查询，每批 500，用 `findPage()` 的 skip/limit 迭代。

### P4.4 EpayClient 安全 + 性能

**文件**: `support/adapter/EpayClient.php:205-207`

- SSL 验证禁用（支付网关！）→ 启用
- `Connection: close`（每次重建 TCP+TLS）→ 移除
- 无 connect_timeout → 加 5 秒
- cURL 错误被吞 → 加 `curl_error()` 日志

---

## P5 — 服务器配置调优

| 文件 | 配置项 | 当前值 | 建议值 | 原因 |
|------|--------|--------|--------|------|
| `config/server.php:8` | count | `cpu_count() * 4` | `cpu_count() * 2` | I/O 密集型应用不需要 4x，减少连接数和内存 |
| `config/server.php:13` | stop_timeout | `2` | `30` | 2 秒太短，外部 HTTP 调用和 CSV 导出可能被截断 |
| `RedisClient.php` | read_timeout | 无 | 2 秒 | 避免 Redis 挂起时 worker 永久阻塞 |

---

## 预期效果总结

| 场景 | 当前 | 优化后 |
|------|------|--------|
| 搜索请求（ES+API） | ~2000ms（串行） | ~1000ms（并行 + 异步日志） |
| 搜索请求（ES only） | ~150ms | ~80ms（_source 过滤 + 1 次额度 SQL） |
| Admin 登录/鉴权（冷缓存） | 4-5 次 DB 查询 | 1 次 DB + 回写 Redis |
| Admin Dashboard | 7 次 DB 查询 | 1 次 SQL + Redis 缓存 |
| 监控页面 | 15+ 次 I/O | 复用 Dashboard 缓存 |
| 代理探测（50 个） | ~500 秒 | ~50 秒（10 并发） |
| syncAll（10 万题） | OOM 风险 | 分批处理，内存稳定 |
| 每次 DB 调用 | +1 次 SELECT 1 | 30 秒内免 ping |
