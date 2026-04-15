<?php

namespace support;

/**
 * Response 兼容层（对齐官方命名）。
 *
 * 当前阶段先保留最小 Json 输出能力，后续由真实 Webman Response 接管。
 */
class Response
{
    public static function json(array $data): array
    {
        return $data;
    }
}
