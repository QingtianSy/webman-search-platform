<?php

namespace app\exception;

use Exception;

class BusinessException extends Exception
{
    protected mixed $data;

    public function __construct(string $message = 'Business Error', int $code = 50001, mixed $data = [])
    {
        parent::__construct($message, $code);
        $this->data = $data;
    }

    public function getData(): mixed
    {
        return $this->data;
    }
}
