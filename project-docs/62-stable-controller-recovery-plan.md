# 稳定优先版 Controller 清单

## 目的
当前项目已经进入真实接入执行阶段，但部分模块在服务器运行态下，
完整链（Request → Validate → Service → Repository/Model → ApiResponse）仍存在稳定性问题。

为保证接口可用性，当前对部分模块先保留**稳定优先版 controller**。

---

## 当前稳定优先版模块
### 已验证可工作
- `backend/app/controller/admin/SystemConfigController.php`
- `backend/app/controller/admin/ApiSourceManageController.php`
- `backend/app/controller/user/DocController.php`
- `backend/app/controller/admin/DocManageController.php`
- `backend/app/controller/user/CollectController.php`
- `backend/app/controller/admin/CollectManageController.php`

---

## 当前策略
### 短期
- 优先保证运行态可用
- 以真实 HTTP 接口稳定为第一目标

### 中期
- 一条链一条链恢复到完整标准版
- 恢复顺序：
  1. system-config
  2. api-source
  3. docs
  4. collect

---

## 当前说明
这些 controller 并不是最终理想形态，但它们已经过服务器真实验证。
在后续回收前，不应随意改回未经验证的复杂版本。
