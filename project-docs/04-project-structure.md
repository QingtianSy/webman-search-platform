# 项目骨架与开发顺序

## 一、后端目录

```bash
backend/
├── app/
├── bootstrap/
├── config/
│   ├── plugin/
├── public/
├── runtime/
├── storage/
├── support/
├── ARCHITECTURE.md
├── start.php
└── composer.json
```

## 二、当前阶段说明
当前项目已从“功能骨架阶段”进入“真接入前收口与准备阶段”：
- 后端 mock 结构已基本完整
- 前端骨架已基本完整
- 统一用户体系与 RBAC 已定型
- 已明确 mock -> real 替换路线
- 已准备 Webman 真接入所需目录占位

## 三、后续重点
1. 批次 1：框架与基础设施接入
2. 批次 2：auth / rbac 真替换
3. 批次 3：question / search 真替换
4. 批次 4：logs / quota 真替换
