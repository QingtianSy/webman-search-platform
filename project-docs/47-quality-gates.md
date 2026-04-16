# Quality gates / 代码质量门禁

## 当前门禁
### 后端
- PHP 语法检查
- Composer validate
- Composer smoke
- auth / search / dashboard / question detail / api key / doc config / collect task detail / rbac smoke

### 脚本
- Shell 语法检查
- Python 语法检查
- JSON 合法性检查

### 前端（当前阶段）
- package.json 存在
- vite.config.ts 存在
- tsconfig.json 存在
- env.d.ts 存在

## 当前说明
由于 Minis 当前环境没有 node/npm，因此前端质量门禁当前以**静态结构检查**为主，而不做 build 验证。

## 推荐执行
- `scripts/check_repo_quality.sh`
- `backend/composer.json` 中的 `composer smoke`
