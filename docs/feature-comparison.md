# 功能对比：爱搜题 vs 我们平台

> 对比日期：2026-04-19
> 参考站点：http://tk.tlicf.com (爱搜题)

---

## 一、用户端功能对比

| 功能模块 | 爱搜题 | 我们平台 | 状态 |
|---------|--------|---------|------|
| 首页/Dashboard | 余额、套餐、额度统计、套餐推荐 | Dashboard overview 接口已有 | ✅ 有 |
| 登录/注册 | 账号密码登录 | JWT 登录，profile/menus/permissions | ✅ 有 |
| 个人中心 - 基本信息 | 个人资料修改 | profile 接口已有 | ✅ 有 |
| 个人中心 - 对接配置 | API Key + 接口地址展示 | API Key CRUD 完整 | ✅ 有 |
| 个人中心 - AI配置 | AI 模型选择(火山引擎 doubao 等) | — | ❌ 缺少 |
| 我的套餐 | 套餐列表、当前套餐 | currentPlan 接口 | ✅ 有 |
| 在线充值 | 支付宝/微信/QQ 三步充值流程 | wallet 接口有，但无支付网关集成 | ⚠️ 缺支付 |
| 聊天会话 | AI 对话(火山引擎 doubao 大模型) | — | ❌ 缺少 |
| 对接文档 | API 文档展示 | doc categories/articles 完整 | ✅ 有 |
| 账号采集 | 超星学习通采集（5种类型） | collect tasks 接口有，缺查课+提交 | ⚠️ 需补接口 |
| 题库配置 | 用户自己管理第三方 API 源 | 用户端 CRUD + 搜索集成已完成 | ✅ 有 |
| 通知公告 | 公告列表、搜索过滤（通知/公告两种类型） | admin CRUD + 用户端列表/详情 | ✅ 有 |
| 搜题功能 | 搜题（核心功能） | search query + quota 扣减 | ✅ 有 |
| 搜题日志 | 题目/选项/答案/题型/状态(成功/失败) | search logs 接口 | ✅ 有 |
| 余额日志 | 日志类型(余额充值/购买套餐)/变动/剩余/备注/时间 | balance log 接口 | ✅ 有 |
| 充值记录 | 订单号/金额/方式/状态(待支付/已支付/已取消/已退款/已过期) | payment log 接口 | ✅ 有 |
| 操作日志 | 模块/类型/人员/地址/地点/状态/耗时 | operate log 接口 | ✅ 有 |
| 登录日志 | 地址/地点/浏览器/OS/状态/信息 | login log 接口 | ✅ 有 |

## 二、管理端功能对比

| 功能模块 | 爱搜题 | 我们平台 | 状态 |
|---------|--------|---------|------|
| 题目管理 - 题目列表 | MD5/题目/选项/答案/题型/来源/课程 筛选 + 新建/删除/导出 | admin 题目 CRUD + CSV 导出 | ✅ 有 |
| 搜题日志(admin) | 添加/删除/导出 + 状态列 | admin 搜题日志（只读 + CSV 导出） | ⚠️ 缺增删 |
| 系统管理 | 有（未展开，具体不明） | system-config 接口 | ⚠️ 不确定 |
| 系统监控 | 有（未展开，具体不明） | 服务器/PHP/Redis/MySQL/业务统计监控 | ✅ 有 |
| 系统工具 | 有（未展开，具体不明） | — | ❌ 可能缺 |

## 三、我们比爱搜题多的功能

| 功能 | 说明 |
|------|------|
| Open API | 完整的第三方开放搜索接口（API Key 鉴权） |
| Admin 后台 | 完整管理系统：题库CRUD、用户/角色/权限/菜单管理 |
| 健康检查 | /health + /ready 探针 |
| RBAC 权限体系 | 用户、角色、权限、动态菜单完整 |
| API Source 管理 | admin 端第三方 API 源管理 + 连通性测试 |
| 系统配置 | key-value 系统配置管理 |
| 系统监控 | 服务器状态/PHP运行时/Redis/MySQL/业务统计一站式监控 |

## 四、搜索 API 参数规范对比

两平台参数规范**完全一致**，说明是行业通用标准：

| 参数 | 爱搜题 | 我们平台 |
|------|--------|---------|
| 关键词参数名 | `q` | `q` |
| 类型选项参数名 | `info` | `info` |
| 选项分隔符 | `###` | `###` |
| 选项格式 | `type****[options]` | 待确认 |
| 响应路径 | `data` | `data` |
| 成功码字段 | `code` | `code` |
| 成功码值 | `1` | `200` |

## 五、账号采集功能详细设计

### 采集流程

```
用户输入超星账号密码
    ↓
点击"查询课程"（POST /queryCourses）
    ↓
返回课程列表（kcid/课程名/教师信息/图片/开课结课时间）
    ↓
选择采集类型 (route 字段)
    ├─ courses：整号采集（自动选中全部课程）
    ├─ course：单课程采集（勾选具体课程）
    ├─ chapter：章节测试（勾选具体课程）
    ├─ homework：作业（勾选具体课程）
    └─ exam：考试（勾选具体课程）
    ↓
提交采集任务（POST /submitCollect）
    ↓
任务状态流转：等待采集(1) → 正在采集(2) → 采集完成(3) / 采集失败(4)
    ↓
可查看课程列表（GET /list → 点击"查看课程"弹窗）
```

### 参考站点 API 接口抓包记录

#### 1. 查询课程 `POST /api/system/accountCollect/queryCourses`

**请求参数：**
```json
{
    "account": "15807782013",
    "password": "@Ww78765"
}
```

**响应（成功）：**
```json
{
    "msg": "操作成功",
    "code": 200,
    "data": {
        "msg": "查询成功",
        "code": 1,
        "courseCount": 15,
        "userName": "韦柳珍",
        "courses": [
            {
                "kcid": "262175449",
                "courseName": "从古至今话廉洁——大学生廉洁素养教育",
                "teacherName": "吉林大学 - 任务点 35/43 - 完成率 81.0% - 开课时间: 2026-04-07 - 结课时间: 2026-05-29",
                "imageUrl": "https://p.ananas.chaoxing.com/star3/240_130c/xxx.jpg",
                "startTime": "2026-04-07",
                "endTime": "2026-05-29",
                "courseId": "262175449"
            }
        ]
    }
}
```

**课程对象字段：**

| 字段 | 类型 | 说明 |
|------|------|------|
| kcid | string | 课程ID（和 courseId 相同） |
| courseName | string | 课程名称 |
| teacherName | string | 教师/学校 + 进度信息（复合字符串） |
| imageUrl | string | 课程封面图 URL |
| startTime | string | 开课时间（"2026-04-07" 或 "不限时"） |
| endTime | string | 结课时间（"2026-05-29" 或 "不限时"） |
| courseId | string | 课程ID |

#### 2. 提交采集 `POST /api/system/accountCollect/submitCollect`

**请求参数（考试 - 单课程）：**
```json
{
    "account": "15807782013",
    "password": "@Ww78765",
    "route": "exam",
    "selectedCourses": "262175449"
}
```

**请求参数（整号采集 - 全部课程）：**
```json
{
    "account": "15807782013",
    "password": "@Ww78765",
    "route": "courses",
    "selectedCourses": "262175449,262175476,231633029,233171553,..."
}
```

**route 枚举值：**

| route 值 | 前端显示 | 说明 |
|----------|---------|------|
| courses | 整号采集 | 采集所有课程 |
| course | 单课程采集 | 采集选中课程 |
| chapter | 章节测试 | 采集章节测试题 |
| homework | 作业 | 采集作业题 |
| exam | 考试 | 采集考试题 |

**响应（成功）：**
```json
{
    "msg": "提交成功",
    "code": 200,
    "data": {
        "id": 33,
        "account": "15807782013",
        "password": "@Ww78765",
        "userName": null,
        "route": "exam",
        "selectedCourses": "262175449",
        "status": "1",
        "courseCount": 1,
        "questionCount": null,
        "errorMsg": null,
        "createBy": "2678785053",
        "createTime": "2026-04-19 03:49:33",
        "updateBy": null,
        "updateTime": null,
        "remark": null
    }
}
```

**任务状态枚举：**

| status | 前端显示 | 说明 |
|--------|---------|------|
| 1 | 等待采集 | 刚提交，排队中 |
| 2 | 正在采集 | 采集程序正在处理 |
| 3 | 采集完成 | 成功 |
| 4 | 采集失败 | 失败，errorMsg 有详情 |

#### 3. 任务列表 `GET /api/system/accountCollect/list?pageNum=1&pageSize=10`

**响应字段（任务列表表格列）：**

| 列 | 字段 | 说明 |
|----|------|------|
| 账号 | account | 超星账号 |
| 采集类型 | route | courses/course/chapter/homework/exam |
| 课程数量 | courseCount | 选中课程数 |
| 题目数量 | questionCount | 已采集题目数 |
| 状态 | status | 1/2/3/4 |
| 错误信息 | errorMsg | 失败原因 |
| 创建时间 | createTime | 任务创建时间 |
| 操作 | — | "查看课程"按钮 |

#### 4. 查看课程弹窗

点击"查看课程"后弹窗显示该任务的课程列表：

| 列 | 说明 |
|----|------|
| 序号 | 自增编号 |
| 课程名称 | 课程名 |
| 题目数量 | 该课程已采集题目数 |
| 状态 | 待采集 / 采集中 / 已完成 / 失败 |

### 我们的后端接口设计

| 接口 | 方法 | 说明 |
|------|------|------|
| `/api/v1/user/collect/query-courses` | POST | 输入超星账号密码，调用查课脚本，返回课程列表 |
| `/api/v1/user/collect/submit` | POST | 提交采集任务（account, password, route, selectedCourses） |
| `/api/v1/user/collect/task/list` | GET | 任务列表（已有，需适配字段） |
| `/api/v1/user/collect/task/detail` | GET | 任务详情（已有） |
| `/api/v1/user/collect/task/courses` | GET | 查看某任务的课程列表及各课程采集状态 |

## 六、题库配置（第三方 API 源）用户端详细字段

### 添加接口表单

**基本配置：**
- 接口名称（必填）
- 请求方式：GET / POST
- 接口地址（必填，如 `https://api.example.com/search?key=xxx`）

**参数配置：**
- 关键词参数：默认 `q`
- 关键词位置：URL参数 / 请求体
- 类型选项参数：默认 `info`
- 类型选项位置：URL参数 / 请求体
- 选项分隔符：默认 `###`
- 选项格式：默认 `type****[options]`（支持变量：type(题型)、[options](选项列表)）

**高级配置：**
- 请求头：JSON格式，如 `{"Authorization": "Bearer xxx"}`
- 扩展配置：JSON格式，支持自定义参数、请求头、变量
- 响应路径：指定响应JSON中答案数据的路径，默认 `data`
- 成功码字段：默认 `code`
- 成功码值：默认 `1`
- 超时(秒)：默认 10
- 排序：默认 0
- 状态：正常 / 停用
- 备注

## 七、待补充功能优先级建议

### P0 - 核心业务

1. **账号采集接口补全** — 查课 + 提交采集（依赖采集程序）
2. **用户端题库配置** — 让用户自己添加/管理第三方搜题 API 源

### P1 - 商业化

3. **在线充值/支付集成** — 支付宝/微信/QQ 支付网关
4. **用户端通知公告查看接口**

### P2 - 增值功能

5. **AI 聊天会话** — 集成大模型（火山引擎/OpenAI等）
6. **AI 配置** — 用户选择 AI 模型/引擎
7. **导出功能** — 题目和日志的 Excel/CSV 导出

### P3 - 运维

8. **系统监控** — 服务器状态、在线用户、请求统计
9. **系统工具** — 缓存管理、代码生成等
