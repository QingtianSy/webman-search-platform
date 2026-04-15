# Webman 真接入前文件准备

## 一、需要提前准备的目录

### 1. public/
用途：
- 真正的 Web 入口目录
- Nginx root 未来应优先指向这里（后端场景）
- 放置入口文件与静态公开资源

### 2. config/plugin/
用途：
- 存放后续插件/组件配置
- 便于 Webman 插件化管理

### 3. runtime/
用途：
- 运行时缓存
- 临时文件
- 进程文件
- 调试输出

### 4. storage/
用途：
- mock 数据（过渡期）
- 上传文件
- 导出文件
- 本地日志占位

---

## 二、当前建议先创建但不强接入的文件
- `backend/public/README.md`
- `backend/config/plugin/README.md`
- `backend/public/index.php`（后续真实接入时再替换）

---

## 三、第一批真实接入时的处理方式
### public/
- 改为真实入口
- 宿主机 Nginx 配置同步调整

### config/plugin/
- 依赖真实插件接入后再逐步补配置

### runtime/
- 正式加入进程与缓存实际使用

---

## 四、当前原则
先把目录和职责定清楚，再做真实框架接入，避免后续反复挪目录。