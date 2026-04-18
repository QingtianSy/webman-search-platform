# API 接口文档

## 1. 通用规范

### 1.1 基础信息
- Base URL: `https://your-domain.com`
- 协议: HTTPS
- 格式: JSON
- 编码: UTF-8

### 1.2 认证方式

| 路由前缀 | 认证方式 | Header |
|---------|---------|--------|
| `/api/v1/auth/login` | 无需认证 | - |
| `/api/v1/user/*` | JWT Bearer Token | `Authorization: Bearer {token}` |
| `/api/v1/admin/*` | JWT Bearer Token（admin 角色） | `Authorization: Bearer {token}` |
| `/open/v1/*` | API Key | `X-Api-Key: {api_key}` |

### 1.3 统一响应格式

成功：
```json
{
    "code": 1,
    "msg": "success",
    "data": {},
    "request_id": "req_abc123"
}
```

失败：
```json
{
    "code": 40001,
    "msg": "参数错误",
    "data": {},
    "request_id": "req_abc123"
}
```

### 1.4 响应码

| code | 含义 |
|------|------|
| 1 | 成功 |
| 40001 | 参数错误 |
| 40002 | 未登录 / Token 无效 |
| 40003 | 无权限 |
| 50001 | 系统错误 |

### 1.5 分页参数

| 参数 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| page | int | 1 | 页码 |
| page_size | int | 20 | 每页条数（最大 100） |
| keyword | string | "" | 搜索关键词 |
| status | int | null | 状态筛选 |
| sort | string | "" | 排序字段 |
| order | string | "desc" | 排序方向 asc/desc |
| start_time | string | "" | 开始时间 |
| end_time | string | "" | 结束时间 |

---

## 2. 认证接口

### POST /api/v1/auth/login - 统一登录

请求：
```json
{
    "username": "admin",
    "password": "123456"
}
```

响应：
```json
{
    "code": 1,
    "msg": "success",
    "data": {
        "token": "eyJhbGciOiJIUzI1NiIs...",
        "user": {
            "id": 1,
            "username": "admin",
            "nickname": "管理员",
            "avatar": "",
            "default_portal": "admin",
            "roles": ["admin"],
            "permissions": ["admin.access", "question.manage"]
        }
    }
}
```

### GET /api/v1/auth/profile - 获取个人信息
### GET /api/v1/auth/menus - 获取菜单树
### GET /api/v1/auth/permissions - 获取权限列表

---

## 3. 用户端接口

### GET /api/v1/user/dashboard/overview - Dashboard 概览

响应：
```json
{
    "code": 1,
    "data": {
        "balance": "100.00",
        "current_plan": {
            "name": "月度套餐",
            "is_unlimited": 0,
            "remain_quota": 500,
            "expire_at": "2026-05-18"
        },
        "today_usage": 15,
        "total_usage": 320,
        "announcements": [],
        "user_id": 1
    }
}
```

### POST /api/v1/user/search/query - 搜题

请求：
```json
{
    "keyword": "TCP三次握手",
    "page": 1,
    "page_size": 10
}
```

响应：
```json
{
    "code": 1,
    "data": {
        "list": [
            {
                "question_id": "Q20260418000001",
                "stem_plain": "TCP建立连接需要几次握手？",
                "type_name": "单选题",
                "answers": ["A"],
                "answer_text": "三次",
                "score": 9.5
            }
        ],
        "total": 1,
        "page": 1,
        "page_size": 10,
        "cost_ms": 45
    }
}
```

### GET /api/v1/user/search/logs - 搜索历史
### GET /api/v1/user/api-key/list - API Key 列表
### GET /api/v1/user/api-key/detail - API Key 详情
### POST /api/v1/user/api-key/create - 创建 API Key
### POST /api/v1/user/api-key/toggle - 启用/禁用 API Key
### DELETE /api/v1/user/api-key/delete - 删除 API Key
### GET /api/v1/user/wallet/detail - 钱包详情
### GET /api/v1/user/plan/current - 当前套餐
### GET /api/v1/user/doc/category/list - 文档分类
### GET /api/v1/user/doc/article/detail - 文档详情
### GET /api/v1/user/doc/config - 文档配置
### GET /api/v1/user/collect/task/list - 采集任务列表
### GET /api/v1/user/collect/task/detail - 采集任务详情
### GET /api/v1/user/log/balance - 余额日志
### GET /api/v1/user/log/payment - 支付日志
### GET /api/v1/user/log/login - 登录日志
### GET /api/v1/user/log/operate - 操作日志

---

## 4. 管理端接口

### 题库管理
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/v1/admin/question/list | 题目列表 |
| GET | /api/v1/admin/question/detail | 题目详情 |
| POST | /api/v1/admin/question/create | 创建题目 |
| PUT | /api/v1/admin/question/update | 更新题目 |
| DELETE | /api/v1/admin/question/delete | 删除题目 |
| GET | /api/v1/admin/question-category/list | 分类列表 |
| GET | /api/v1/admin/question-type/list | 题型列表 |
| GET | /api/v1/admin/question-source/list | 来源列表 |
| GET | /api/v1/admin/question-tag/list | 标签列表 |

### 用户管理
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/v1/admin/user/list | 用户列表 |
| GET | /api/v1/admin/role/list | 角色列表 |
| GET | /api/v1/admin/permission/list | 权限列表 |
| GET | /api/v1/admin/menu/list | 菜单列表 |
| GET | /api/v1/admin/plan/list | 套餐列表 |

### 内容管理
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/v1/admin/announcement/list | 公告列表 |
| POST | /api/v1/admin/announcement/create | 创建公告 |
| PUT | /api/v1/admin/announcement/update | 更新公告 |
| DELETE | /api/v1/admin/announcement/delete | 删除公告 |
| GET | /api/v1/admin/doc/article/list | 文档列表 |
| POST | /api/v1/admin/doc/article/create | 创建文档 |
| PUT | /api/v1/admin/doc/article/update | 更新文档 |
| DELETE | /api/v1/admin/doc/article/delete | 删除文档 |

### 采集管理
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/v1/admin/collect/task/list | 任务列表 |
| GET | /api/v1/admin/collect/task/detail | 任务详情 |
| POST | /api/v1/admin/collect/task/stop | 停止任务 |
| POST | /api/v1/admin/collect/task/retry | 重试任务 |

### 系统管理
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/v1/admin/api-source/list | API 源列表 |
| GET | /api/v1/admin/api-source/detail | API 源详情 |
| POST | /api/v1/admin/api-source/test | 测试 API 源 |
| GET | /api/v1/admin/system-config/list | 系统配置列表 |
| POST | /api/v1/admin/system-config/update | 更新系统配置 |
| GET | /api/v1/admin/log/search/list | 搜索日志列表 |

---

## 5. 开放平台接口

### POST /open/v1/search/query - API 搜题

Header:
```
X-Api-Key: your_api_key_here
```

请求：
```json
{
    "keyword": "TCP三次握手",
    "page": 1,
    "page_size": 10
}
```

响应：同用户端搜题接口

### GET /open/v1/quota/detail - 额度查询

响应：
```json
{
    "code": 1,
    "data": {
        "total_quota": 10000,
        "remain_quota": 8500,
        "used_quota": 1500,
        "is_unlimited": false
    }
}
```

### GET /open/v1/health - 健康检查

响应：
```json
{
    "code": 1,
    "data": {
        "status": "ok",
        "version": "1.0.0",
        "timestamp": "2026-04-18T20:00:00+08:00"
    }
}
```

---

## 6. 公共接口

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | / | 首页（版本信息） |
| GET | /health | 健康检查 |
| GET | /ready | 就绪检查（含数据库连通性） |
