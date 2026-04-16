# Search real HTTP preparation

## 当前新增
- `ElasticsearchClient` 已支持 HTTPS host + Basic Auth + 关闭证书校验占位
- `QuestionIndexRepository::searchReal()` 已具备通过 Guzzle 调 ES `_search` 的骨架

## 当前限制
- 当前仅完成 HTTP 调用骨架
- 还未在真实服务器上完成 ES 搜索验证
- 还未切 `QUESTION_SOURCE=real`

## 下一步
1. 确认 `.env` 的 ES_HOST / ES_USERNAME / ES_PASSWORD
2. 先做一次后端侧真实搜索调用测试
3. 再切 `QUESTION_SOURCE=real`
