<?php

namespace support;

/**
 * Request 兼容占位层。
 *
 * 当前阶段：
 * - 为 mock / 轻量控制器调用提供统一取参接口
 *
 * 后续阶段：
 * - 逐步替换为真实 Webman Request
 * - 控制器方法签名优先保持兼容，减少后续改动范围
 */
class Request
{
    public function __construct(
        protected array $get = [],
        protected array $post = [],
        protected array $headers = []
    ) {
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->get[$key] ?? $default;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        $normalized = strtolower($key);
        $headers = array_change_key_case($this->headers, CASE_LOWER);
        return $headers[$normalized] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->get, $this->post);
    }
}
