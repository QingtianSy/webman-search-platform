<?php

namespace support;

/**
 * InputRequest 兼容别名层。
 *
 * 保留原因：
 * - 当前项目已有大量 InputRequest 引用
 * - 后续逐步收敛回官方 Request 命名时，先保持兼容，避免大面积改动
 */
class InputRequest extends Request
{
}
