<?php

namespace support;

class Request
{
    public function __construct(protected array $get = [], protected array $post = [], protected array $headers = [])
    {
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
}
