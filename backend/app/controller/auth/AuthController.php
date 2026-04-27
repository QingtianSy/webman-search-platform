<?php

namespace app\controller\auth;

use app\exception\BusinessException;
use app\repository\mysql\LoginLogRepository;
use app\repository\mysql\OperateLogRepository;
use app\repository\mysql\UserRepository;
use app\service\auth\AuthService;
use app\service\auth\JwtService;
use app\service\auth\PasswordService;
use app\validate\auth\RegisterValidate;
use support\ApiResponse;
use support\Db;
use support\Request;

class AuthController
{
    public function login(Request $request)
    {
        $username = (string) $request->post('username', '');
        $password = (string) $request->post('password', '');
        $ip = $request->getRealIp();
        $ua = (string) $request->header('User-Agent', '');

        $authService = new AuthService();
        $jwtService = new JwtService();
        $payload = $authService->login($username, $password);
        if (!$payload) {
            (new LoginLogRepository())->create(['user_id' => 0, 'ip' => $ip, 'user_agent' => $ua, 'status' => 0]);
            return ApiResponse::error(40002, '账号或密码错误');
        }

        $user = $payload['user'];

        // 登录 bump sessions_invalidated_at，让早于本次登录签发的 token 被中间件拒绝。
        // Redis 缓存 + DB bump 已在 issueSessionToken 内原子执行。
        // 登录成功日志必须等 token 签发后才写：若 issueSessionToken 抛错，客户端拿到的是失败，
        // 此时不应留下一条 status=1 的审计记录。
        $token = $authService->issueSessionToken((int) $user['id'], [
            'uid' => $user['id'],
            'username' => $user['username'],
            'roles' => $payload['roles'],
            'default_portal' => $payload['default_portal'] ?? 'user',
        ]);

        (new LoginLogRepository())->create(['user_id' => $user['id'], 'ip' => $ip, 'user_agent' => $ua, 'status' => 1]);

        return ApiResponse::success([
            'token' => $token,
            'expire_at' => time() + (int) config('jwt.expire', 604800),
            'user' => $user,
            'roles' => $payload['roles'],
            'permissions' => $payload['permissions'],
            'menus' => $payload['menus'],
            'default_portal' => $payload['default_portal'],
        ], '登录成功');
    }

    public function profile(Request $request)
    {
                $authorization = (string) $request->header('Authorization', '');
        $token = preg_match('/^Bearer\s+(.+)$/i', $authorization, $m) ? $m[1] : '';
        $decoded = (new JwtService())->decode($token);
        $userId = (int) (($decoded['payload']['uid'] ?? 0));
        $payload = (new AuthService())->profile($userId);
        if (!$payload) {
            return ApiResponse::error(40002, '未登录或用户不存在');
        }
        return ApiResponse::success($payload);
    }

    public function menus(Request $request)
    {
                $authorization = (string) $request->header('Authorization', '');
        $token = preg_match('/^Bearer\s+(.+)$/i', $authorization, $m) ? $m[1] : '';
        $decoded = (new JwtService())->decode($token);
        $userId = (int) (($decoded['payload']['uid'] ?? 0));
        $payload = (new AuthService())->profile($userId);
        return ApiResponse::success($payload['menus'] ?? []);
    }

    public function permissions(Request $request)
    {
                $authorization = (string) $request->header('Authorization', '');
        $token = preg_match('/^Bearer\s+(.+)$/i', $authorization, $m) ? $m[1] : '';
        $decoded = (new JwtService())->decode($token);
        $userId = (int) (($decoded['payload']['uid'] ?? 0));
        $payload = (new AuthService())->profile($userId);
        return ApiResponse::success($payload['permissions'] ?? []);
    }

    public function register(Request $request)
    {
        $data = (new RegisterValidate())->register($request->post());
        $authService = new AuthService();
        $payload = $authService->register($data);

        // register() 已在事务内完成 users/wallets/user_role + sessions_invalidated_at bump，
        // 并在 payload 中返回同一 iat_ms 签发的 token，Redis 缓存为 best-effort。
        // 这样即便 Redis 写失败也不会出现"账号已建但接口回 500"的半成品。
        $user = $payload['user'];
        $token = $payload['token'];

        return ApiResponse::success([
            'token' => $token,
            'expire_at' => time() + (int) config('jwt.expire', 604800),
            'user' => $user,
            'roles' => $payload['roles'],
            'permissions' => $payload['permissions'],
            'menus' => $payload['menus'],
            'default_portal' => $payload['default_portal'],
        ], '注册成功');
    }

    public function updateProfile(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $data = array_filter([
            'nickname' => trim((string) $request->post('nickname', '')),
            'email' => trim((string) $request->post('email', '')),
            'mobile' => trim((string) $request->post('phone', '')),
            'avatar' => trim((string) $request->post('avatar', '')),
        ], fn ($v) => $v !== '');

        if (empty($data)) {
            return ApiResponse::error(40001, '没有需要更新的字段');
        }

        $result = (new AuthService())->updateProfile($userId, $data);
        if (!$result) {
            return ApiResponse::error(40002, '更新失败');
        }
        return ApiResponse::success($result, '更新成功');
    }

    // 登出：中间件已校验并把 userId 挂到 request 上；service 内把 sessions_invalidated_at 推进到 now，
    // 同时清 Redis token 缓存与合并鉴权缓存。幂等——重复调用仍会刷新 invalidated_ms。
    public function logout(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        if ($userId <= 0) {
            // 走到这里说明 UserAuthMiddleware 已放行却没挂 userId，理论不可达；兜底走 40002 而非 500 以便前端清 token。
            return ApiResponse::error(40002, '未登录');
        }
        (new AuthService())->logout($userId);
        return ApiResponse::success(null, '已登出');
    }

    // 改密码：校验旧密码 → 写新 hash → bump sessions_invalidated_at（所有活跃 token 立刻失效，
    // 包括本次请求自己携带的那条；前端改密成功后需重新登录）。
    public function changePassword(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        if ($userId <= 0) {
            return ApiResponse::error(40002, '未登录');
        }
        $old = (string) $request->post('old_password', '');
        $new = (string) $request->post('new_password', '');
        if ($old === '' || $new === '') {
            return ApiResponse::error(40001, '原密码与新密码不能为空');
        }
        if (strlen($new) < 6) {
            return ApiResponse::error(40001, '新密码至少 6 位');
        }
        if ($old === $new) {
            return ApiResponse::error(40001, '新密码不能与原密码相同');
        }

        try {
            $user = (new UserRepository())->findByIdStrict($userId);
        } catch (\RuntimeException $e) {
            throw new BusinessException('用户服务暂不可用，请稍后重试', 50001);
        }
        if (!$user) {
            return ApiResponse::error(40002, '用户不存在');
        }

        if (!(new PasswordService())->verify($old, $user)) {
            return ApiResponse::error(40004, '原密码不正确');
        }

        $hash = password_hash($new, PASSWORD_DEFAULT);
        $ok = Db::table('users')->where('id', $userId)->update([
            'password_hash' => $hash,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        if ($ok === false) {
            throw new BusinessException('密码更新失败，请稍后重试', 50001);
        }

        // 全站会话失效：复用 logout 的 bump + bust，让本次请求之后的调用立刻被拒
        (new AuthService())->logout($userId);

        (new OperateLogRepository())->create([
            'user_id' => $userId,
            'module' => 'auth',
            'action' => 'change_password',
            'content' => '修改登录密码',
            'ip' => $request->getRealIp(),
        ]);

        return ApiResponse::success(null, '密码已更新，请重新登录');
    }

    // 强制下线：把 sessions_invalidated_at 推到 now+1ms，本次 token 也会被废弃。
    // 前端应在 200 后主动清本地 token 并跳登录。
    public function invalidateSessions(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        if ($userId <= 0) {
            return ApiResponse::error(40002, '未登录');
        }
        (new AuthService())->logout($userId);
        (new OperateLogRepository())->create([
            'user_id' => $userId,
            'module' => 'auth',
            'action' => 'invalidate_sessions',
            'content' => '强制所有会话下线',
            'ip' => $request->getRealIp(),
        ]);
        return ApiResponse::success(null, '所有会话已下线');
    }

    // 头像上传：multipart/form-data，字段名 file；落 public/avatars/{uid}_{ts}.{ext}；
    // 写回 users.avatar 为相对 URL（/avatars/xxx），前端按 API_BASE 拼接或直接走静态。
    public function uploadAvatar(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        if ($userId <= 0) {
            return ApiResponse::error(40002, '未登录');
        }
        $file = $request->file('file');
        if (!$file || !$file->isValid()) {
            return ApiResponse::error(40001, '请选择头像文件');
        }
        $size = $file->getSize();
        if ($size <= 0 || $size > 2 * 1024 * 1024) {
            return ApiResponse::error(40001, '头像不能超过 2MB');
        }
        $ext = strtolower($file->getUploadExtension() ?: '');
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            return ApiResponse::error(40001, '仅支持 jpg/png/gif/webp');
        }

        $dir = public_path() . DIRECTORY_SEPARATOR . 'avatars';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new BusinessException('头像目录不可写', 50001);
        }
        $name = $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $path = $dir . DIRECTORY_SEPARATOR . $name;
        if (!$file->move($path)) {
            throw new BusinessException('头像保存失败', 50001);
        }

        $url = '/avatars/' . $name;
        try {
            (new UserRepository())->updateProfileStrict($userId, ['avatar' => $url]);
        } catch (\RuntimeException $e) {
            throw new BusinessException('用户信息服务暂不可用', 50001);
        }

        (new OperateLogRepository())->create([
            'user_id' => $userId,
            'module' => 'auth',
            'action' => 'upload_avatar',
            'content' => '更新头像 ' . $url,
            'ip' => $request->getRealIp(),
        ]);

        return ApiResponse::success(['url' => $url], '头像已更新');
    }
}
