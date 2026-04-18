<?php

namespace support;

class Request extends \Webman\Http\Request
{
    /**
     * 缓存解析后的 JSON body
     */
    protected ?array $_jsonBody = null;

    /**
     * 解析 JSON body（仅解析一次）
     */
    protected function parseJsonBody(): array
    {
        if ($this->_jsonBody === null) {
            $contentType = $this->header('content-type', '');
            if (str_contains($contentType, 'application/json')) {
                $this->_jsonBody = json_decode($this->rawBody(), true) ?: [];
            } else {
                $this->_jsonBody = [];
            }
        }
        return $this->_jsonBody;
    }

    /**
     * 重写 post() 方法，兼容 JSON body
     *
     * @param string|null $name
     * @param mixed $default
     * @return mixed
     */
    public function post($name = null, $default = null)
    {
        $post = parent::post();
        if (empty($post)) {
            $post = $this->parseJsonBody();
        }
        if ($name === null) {
            return $post;
        }
        return $post[$name] ?? $default;
    }

    /**
     * 重写 input() 方法，兼容 JSON body
     *
     * @param string|null $name
     * @param mixed $default
     * @return mixed
     */
    public function input($name = null, $default = null)
    {
        $data = parent::all();
        if (empty($data) || ($name !== null && !isset($data[$name]))) {
            $json = $this->parseJsonBody();
            $data = array_merge($json, $data);
        }
        if ($name === null) {
            return $data;
        }
        return $data[$name] ?? $default;
    }
}
