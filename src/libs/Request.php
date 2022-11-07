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
     * 控制器
     *
     * @var string
     */
    public $controller = '';

    /**
     * 控制器回调方法
     *
     * @var string
     */
    public $action = '';

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
        if (is_array($input)) {
            return array_map('htmlspecialchars', (array)$input);
        }

        return htmlspecialchars($input ?: '');
    }

    /**
     * 获取数据, 支持通过'.'分割获取无限级节点数据
     *
     * @param  array  $data 数据源
     * @param  string $name 字段名
     * @param  mixed  $default 默认值
     * @return mixed
     */
    protected function getData(array $data, string $name, $default = null)
    {
        foreach (explode('.', $name) as $val) {
            if (isset($data[$val])) {
                $data = $data[$val];
            } else {
                return $default;
            }
        }

        return $data;
    }
}
