# 后台模块开发规范（Admin Module Convention）

## 目标
让后台模块在企业级项目中保持：
- 结构统一
- 查询行为统一
- 写操作返回风格统一
- 可维护、可扩展

---

## 一、推荐层次
后台模块优先采用：
- `controller/admin/*`
- `validate/admin/*`
- `service/admin/*`
- `common/admin/*`
- `model/admin/*`（逐步 ORM 化）

---

## 二、controller 约定
### 列表接口
- 方法签名：`index(Request $request)` / `list(Request $request)`
- 参数读取：`$request->get()`
- 校验：`AdminQueryValidate()->list(...)`
- 返回：`ApiResponse::success($service->getList($query))`

### 写接口
- create / update / test / stop / retry：
  - 参数读取：`$request->post()`
  - 校验：对应 `validate/admin/*`
  - 返回：`ApiResponse::success(...)`

### delete 接口
- 当前项目暂允许继续使用 `post()` 或显式 id 参数解析
- 后续可进一步统一

---

## 三、service 约定
### 列表返回结构
统一返回：
```php
[
  'list' => [],
  'total' => 0,
  'page' => 1,
  'page_size' => 20,
]
```

### 写操作返回结构
推荐统一返回：
```php
[
  'success' => true,
  'action' => 'create|update|delete|test|stop|retry',
  'id' => null,
  'data' => [],
]
```

---

## 四、查询公共能力
后台列表尽量统一使用：
- `AdminQuery`
- `AdminQueryValidate`
- `AdminListBuilder`
- `AdminStatusFilter`
- `AdminSort`
- `AdminTimeRange`

---

## 五、ORM 第一阶段原则
- `real` 分支优先尝试走 `model/admin/*`
- `mock` 分支保留 `repository` 兜底
- 不一次性全量切 ORM
- 先让核心模块逐步进入 model 驱动

---

## 六、当前说明
当前项目已经在多后台模块中落地本规范，后续新增/改造后台模块时，应优先遵循本文件。
