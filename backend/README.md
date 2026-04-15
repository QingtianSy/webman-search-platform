# backend

Webman 后端代码目录。

## 当前状态
当前代码已进入 **工程收束与真接入友好化阶段**：
- admin 聚合控制器已拆分为一类一文件
- `support/Request` 已重命名收敛为 `support/InputRequest`
- 已消除继续真接入时最明显的自动加载与命名冲突风险
- 后续接 Webman 原生 Request / autoload 会更顺

## 本阶段成果
- 一类一文件控制器结构
- InputRequest 兼容层
- 旧 Request 标记为 deprecated
