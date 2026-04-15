<?php

namespace support;

class ResponseCode
{
    public const SUCCESS = 1;
    public const PARAM_ERROR = 40001;
    public const UNAUTHORIZED = 40002;
    public const FORBIDDEN = 40003;
    public const NOT_FOUND = 40004;
    public const STATUS_ERROR = 40005;
    public const QUOTA_NOT_ENOUGH = 40006;
    public const RATE_LIMITED = 40007;
    public const API_KEY_INVALID = 40008;
    public const IP_DENIED = 40009;
    public const SYSTEM_ERROR = 50001;
    public const DB_ERROR = 50002;
    public const THIRD_API_ERROR = 50003;
    public const SEARCH_SERVICE_ERROR = 50004;
    public const CACHE_ERROR = 50005;
}
