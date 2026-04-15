# backend

Webman 后端代码目录。

## 当前状态
当前代码已进入 **真接入执行顺序明确阶段**：
- auth/rbac 主线已具备 real 查询骨架
- question/search 主线已具备 real 分支骨架
- 现在最重要的是按顺序执行，而不是继续扩 mock

## 真接入总清单
- `project-docs/25-real-integration-master-checklist.md`

## 当前建议优先级
1. auth/rbac 建表与 seed
2. 切 `AUTH_RBAC_SOURCE=real`
3. 验证统一认证
4. 再进入 question/search 主线
