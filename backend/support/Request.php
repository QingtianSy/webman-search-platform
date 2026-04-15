<?php

namespace support;

/**
 * Request 兼容层（对齐官方命名）。
 *
 * 当前阶段：
 * - 作为项目内部统一请求取值接口
 * - 与后续真实 Webman Request 的替换边界保持一致
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
