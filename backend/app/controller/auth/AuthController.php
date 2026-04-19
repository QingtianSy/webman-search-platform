<?php

namespace app\controller\auth;

use app\service\auth\AuthService;
use app\service\auth\JwtService;
use app\validate\auth\RegisterValidate;
use support\ApiResponse;
use support\Request;

class AuthController
{
    public function login(Request $request)
    {
        $username = (string) $request->post('username', '');
        $password = (string) $request->post('password', '');

        $authService = new AuthService();
        $jwtService = new JwtService();
        $payload = $authService->login($username, $password);
        if (!$payload) {
            return ApiResponse::error(40002, '账号或密码错误');
        }

        $user = $payload['user'];
        $token = $jwtService->encode([
            'uid' => $user['id'],
            'username' => $user['username'],
            'roles' => $payload['roles'],
            'default_portal' => $payload['default_portal'] ?? 'user',
        ]);

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
        $token = trim(str_replace('Bearer', '', $authorization));
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
        $token = trim(str_replace('Bearer', '', $authorization));
        $decoded = (new JwtService())->decode($token);
        $userId = (int) (($decoded['payload']['uid'] ?? 0));
        $payload = (new AuthService())->profile($userId);
        return ApiResponse::success($payload['menus'] ?? []);
    }

    public function permissions(Request $request)
    {
                $authorization = (string) $request->header('Authorization', '');
        $token = trim(str_replace('Bearer', '', $authorization));
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

        $user = $payload['user'];
        $token = (new JwtService())->encode([
            'uid' => $user['id'],
            'username' => $user['username'],
            'roles' => $payload['roles'],
            'default_portal' => $payload['default_portal'] ?? 'user',
        ]);

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
}
