# 服务器最小运行版回收清单

## 当前判断
服务器当前为了保证接口先恢复可用，部分模块仍运行的是**最小 controller 版本**，不一定等同于仓库里的完整版实现。

## 当前已确认存在“最小版”风险的模块
### 第一批（优先回收）
- `SystemConfigController`
- `ApiSourceManageController`

### 第二批
- `DocController`
- `DocManageController`

### 第三批
- `CollectController`
- `CollectManageController`

## 当前策略
- 不全量一次性切回完整版
- 按模块逐步从服务器最小版回收至仓库完整版
- 每回收一个模块，就重新验证该模块接口

## 记录建议
后续每次回收后，在本清单中标记：
- 已回收
- 已验证
- 仍需继续观察
