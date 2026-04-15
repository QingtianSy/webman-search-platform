# Workerman / Webman 长驻进程安全清单

## 一、禁止项
- 不要把当前用户、当前请求参数、当前 token 等数据存到 static 变量
- 不要把请求态数据挂到单例 service 的属性上长期保留
- 不要在 repository 内部缓存某次请求特有的数据
- 不要假设每次请求都会重新初始化所有变量

## 二、推荐项
- Controller 只处理当前请求的输入输出
- Service 只做业务编排，不持有请求态
- Repository 只做数据访问，不保留请求态
- InputRequest 仅做兼容层，后续交给 Webman 原生 Request

## 三、后续真实接入前要重点复查的类
- AuthService
- SearchService
- DashboardService
- QuotaService
- 各 Repository 的 real 分支

## 四、为什么重要
Workerman / Webman 是常驻进程模型，如果代码带有“请求结束就全部销毁”的思维，后续会出现：
- 数据串请求
- 状态污染
- 难排查的间歇性问题
- 长期运行后异常行为

## 五、当前项目现状
当前项目整体已经较少使用全局状态，但在真实接入前，仍应再做一轮类级别审查。
