# backend 目录规则

## 一、Controller 规则
- Controller 只负责接参与返回
- 不在 Controller 中直接操作文件、数据库、搜索索引
- 尽量不写复杂业务判断

## 二、Service 规则
- Service 负责业务编排
- 可组合多个 Repository
- 可处理权限、额度、日志、流程控制

## 三、Repository 规则
- Repository 只关心数据存取
- mock 阶段读写 JSON
- real 阶段切换到 MySQL / MongoDB / ES / Redis
- 不在 Repository 里写业务流程

## 四、support 规则
- 放通用工具、响应、分页、适配层
- 不要把业务逻辑塞进 support

## 五、mock 数据规则
- 只允许放过渡阶段必要数据
- 真实数据库接入后逐步移除
- 所有 mock 文件都应有明确替代目标

## 六、优先级规则
- 先统一身份体系
- 再题库与搜题
- 再用户中心与计费
- 再采集与配置
