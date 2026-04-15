# 项目骨架与开发顺序

## 一、后端目录

```bash
backend/
├── app/
│   ├── controller/
│   │   ├── admin/
│   │   ├── auth/
│   │   ├── open/
│   │   └── user/
│   ├── service/
│   │   ├── auth/
│   │   ├── open/
│   │   ├── question/
│   │   ├── search/
│   │   ├── quota/
│   │   ├── log/
│   │   ├── user/
│   │   └── system/
│   ├── repository/
│   │   ├── mysql/
│   │   ├── mongo/
│   │   ├── es/
│   │   └── redis/
│   ├── middleware/
│   ├── validate/
│   ├── exception/
│   ├── common/
│   └── ...
├── bootstrap/
├── config/
├── runtime/
├── storage/
├── support/
├── ARCHITECTURE.md
├── start.php
└── composer.json
```

## 二、当前阶段说明
当前后端已经完成：
- 统一用户体系收敛
- 用户端 / 管理端 / 开放平台路由初步完整
- mock 数据源覆盖主要业务模块
- 管理端操作型接口已进入第三轮
- 宿主机部署与运维脚本已补齐

## 三、下一阶段目标
下一阶段不再盲目扩展 mock 文件，而是按文档推进：
- `07-mock-to-real-plan.md`
- `08-migration-plan.md`
- `09-webman-integration-plan.md`

## 四、真接入优先顺序
1. auth / rbac
2. question / search
3. logs / quota
4. user-center
5. collect / docs / config
