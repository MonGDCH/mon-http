<?php

declare(strict_types=1);

namespace mon\http\libs;

/**
 * 请求实例，公共trait
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
trait Request
{
    /**
     * 路由参数
     *
     * @var array
     */
    public array $params = [];

    /**
     * 控制器
     *
     * @var string
     */
    public string $controller = '';

    /**
     * 控制器回调方法
     *
     * @var string
     */
    public string $action = '';

    /**
     * php:input数据json_decode后的数据
     *
     * @var array
     */
    protected ?array $jsonData = null;

    /**
     * php:input数据xml解析后的数据
     *
     * @var array
     */
    protected ?array $xmlData = null;

    /**
     * 获取路由参数
     *
     * @param mixed  $name      参数键名
     * @param mixed  $default   默认值
     * @param boolean $filter   是否过滤参数
     * @return mixed
     */
    public function params(?string $name = null, mixed $default = null, bool $filter = true): mixed
    {
        $result = is_null($name) ? $this->params : $this->getData($this->params, $name, $default);

        return $filter ? $this->filter($result) : $result;
    }

    /**
     * 获取控制器名称
     *
     * @return string
     */
    public function controller(): string
    {
        return $this->controller;
    }

    /**
     * 获取控制器回调方法名称
     *
     * @return string
     */
    public function action(): string
    {
        return $this->action;
    }

    /**
     * 获取application/json参数
     *
     * @param mixed $name       参数键名
     * @param mixed $default    默认值
     * @param boolean $filter   是否过滤参数
     * @return mixed
     */
    public function json(?string $name = null, mixed $default = null, bool $filter = true): mixed
    {
        if (is_null($this->jsonData)) {
            $input = $this->rawBody();
            if (!$input) {
                return $default;
            }
            $this->jsonData = (array)json_decode($input, true);
        }

        $result = is_null($name) ? $this->jsonData : $this->getData($this->jsonData, $name, $default);

        return $filter ? $this->filter($result) : $result;
    }

    /**
     * 获取application/xml参数
     *
     * @param mixed $name       参数键名
     * @param mixed $default    默认值
     * @param boolean $filter   是否过滤参数
     * @return mixed
     */
    public function xml(?string $name = null, mixed $default = null, bool $filter = true): mixed
    {
        if (is_null($this->xmlData)) {
            $input = $this->rawBody();
            if (!$input) {
                return $default;
            }
            // 开启内部错误处理
            libxml_use_internal_errors(true);
            // 尝试将字符串解析为 XML
            $xml = simplexml_load_string($input);
            // 获取所有解析错误
            $errors = libxml_get_errors();
            // 清除错误列表
            libxml_clear_errors();
            // 如果没有错误，说明是有效的 XML
            $this->xmlData = empty($errors) ? (array)json_decode(json_encode($xml), true) : [];
        }

        $result = is_null($name) ? $this->xmlData : $this->getData($this->xmlData, $name, $default);

        return $filter ? $this->filter($result) : $result;
    }

    /**
     * 是否GET请求
     *
     * @return boolean
     */
    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    /**
     * 是否POST请求
     *
     * @return boolean
     */
    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /**
     * 是否PUT请求
     *
     * @return boolean
     */
    public function isPut(): bool
    {
        return $this->method() === 'PUT';
    }

    /**
     * 是否DELETE请求
     *
     * @return boolean
     */
    public function isDelete(): bool
    {
        return $this->method() === 'DELETE';
    }

    /**
     * 是否PATCH请求
     *
     * @return boolean
     */
    public function isPatch(): bool
    {
        return $this->method() === 'PATCH';
    }

    /**
     * 是否HEAD请求
     *
     * @return boolean
     */
    public function isHead(): bool
    {
        return $this->method() === 'HEAD';
    }

    /**
     * 是否OPTIONS请求
     *
     * @return boolean
     */
    public function isOptions(): bool
    {
        return $this->method() === 'OPTIONS';
    }

    /**
     * 数据安全过滤，采用htmlspecialchars函数
     * 
     * @param  string|array $input 过滤的数据
     * @return mixed
     */
    public function filter($input)
    {
        if (is_numeric($input)) {
            return $input;
        }
        if (is_array($input)) {
            return $this->filterArray((array)$input);
        }

        return htmlspecialchars((string)$input ?: '');
    }

    /**
     * 递归处理数字型参数数据
     *
     * @param array $input
     * @return array
     */
    protected function filterArray(array $input): array
    {
        $result = [];
        foreach ($input as $key => $value) {
            if (is_numeric($value)) {
                $result[$key] = $value;
            } elseif (is_array($value)) {
                $result[$key] = $this->filterArray($value);
            } else {
                $result[$key] = htmlspecialchars((string)$value ?: '');
            }
        }

        return $result;
    }

    /**
     * 获取数据, 支持通过'.'分割获取无限级节点数据
     *
     * @param  array  $data 数据源
     * @param  string $name 字段名
     * @param  mixed  $default 默认值
     * @return mixed
     */
    protected function getData(array $data, string $name, mixed $default = null): mixed
    {
        foreach (explode('.', $name) as $val) {
            if (array_key_exists($val, $data)) {
                $data = $data[$val];
            } else {
                return $default;
            }
        }

        return $data;
    }
}
