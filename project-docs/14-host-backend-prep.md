# 宿主机后端真实接入准备

## 一、基础要求
- PHP 8.2
- Composer 2.x
- Swoole 5.1.x
- MongoDB 扩展
- Redis 扩展

## 二、建议命令顺序
1. 进入后端目录
2. 检查 composer.json
3. 执行 `composer install --no-dev --optimize-autoloader`
4. 检查 autoload
5. 再进入下一批 auth/rbac 真替换

## 三、当前建议脚本
- `scripts/prepare_backend_dependencies.sh`

## 四、风险提示
- 当前仓库仍有 mock 过渡结构
- 依赖接入后不要立刻删除 mock，先替换认证主线
- 不要跳过 auth/rbac 直接去改题库主线
