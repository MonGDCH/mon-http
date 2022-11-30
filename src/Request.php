<?php

declare(strict_types=1);

namespace mon\http;

use InvalidArgumentException;
use mon\http\libs\Request as LibsRequest;
use mon\http\interfaces\RequestInterface;

/**
 * 请求类门面实体，用于统一兼容处理wokerman和fpm环境
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Request implements RequestInterface
{
    use LibsRequest;

    /**
     * 请求实例
     *
     * @var RequestInterface
     */
    protected $service;

    /**
     * 构造方法
     *
     * @param RequestInterface $service
     */
    public function __construct(RequestInterface $service)
    {
        $this->service = $service;
    }

    /**
     * 获取服务实例
     *
     * @return RequestInterface
     */
    public function service(): RequestInterface
    {
        return $this->service;
    }

    /**
     * 获取GET数据
     *
     * @param mixed  $name      参数键名
     * @param mixed  $default   默认值
     * @param boolean $filter   是否过滤参数
     * @return mixed
     */
    public function get($name = null, $default = null, bool $filter = true)
    {
        return $this->service()->get($name, $default, $filter);
    }

    /**
     * 获取POST数据
     *
     * @param mixed  $name      参数键名
     * @param mixed  $default   默认值
     * @param boolean $filter   是否过滤参数
     * @return mixed
     */
    public function post($name = null, $default = null, bool $filter = true)
    {
        return $this->service()->post($name, $default, $filter);
    }

    /**
     * 获取application/json参数
     *
     * @param mixed $name       参数键名
     * @param mixed $default    默认值
     * @param boolean $filter   是否过滤参数
     * @return mixed
     */
    public function json($name = null, $default = null, bool $filter = true)
    {
        return $this->service()->json($name, $default, $filter);
    }

    /**
     * 获取header信息
     *
     * @param mixed $name    参数键名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function header($name = null, $default = null)
    {
        return $this->service()->header($name, $default);
    }

    /**
     * 获取$_SERVER数据
     *
     * @param  mixed  $name    参数键名
     * @param  mixed  $default 默认值
     * @return mixed
     */
    public function server($name = null, $default = null)
    {
        return $this->service()->server($name, $default);
    }

    /**
     * 获取请求Session
     *
     * @return mixed
     */
    public function session()
    {
        return $this->service()->session();
    }

    /**
     * 获取请求Cookie
     *
     * @return mixed
     */
    public function cookie()
    {
        return $this->service()->cookie();
    }

    /**
     * 获取上传文件
     *
     * @param mixed $name 文件参数名
     * @return mixed
     */
    public function file($name = null)
    {
        return $this->service()->file();
    }

    /**
     * 获取请求类型
     *
     * @return string
     */
    public function method(): string
    {
        return $this->service()->method();
    }

    /**
     * 获取请求host
     *
     * @return string
     */
    public function host(): string
    {
        return $this->service()->host();
    }

    /**
     * 获取请求pathinfo路径
     *
     * @return string
     */
    public function path(): string
    {
        return $this->service()->path();
    }

    /**
     * 获取请求URI
     *
     * @return string
     */
    public function uri(): string
    {
        return $this->service()->uri();
    }

    /**
     * 获取当前请求的域名
     *
     * @return string
     */
    public function url(): string
    {
        return $this->service()->url();
    }

    /**
     * 请求完整URL
     *
     * @return string
     */
    public function fullUrl(): string
    {
        return $this->service()->fullUrl();
    }

    /**
     * 获取真实IP
     *
     * @return string
     */
    public function ip(): string
    {
        return $this->service()->ip();
    }

    /**
     * 获取HTTP协议版本号
     *
     * @return string
     */
    public function protocolVersion(): string
    {
        return $this->service()->protocolVersion();
    }

    /**
     * 是否Ajax
     *
     * @return boolean
     */
    public function isAjax(): bool
    {
        return $this->service()->isAjax();
    }

    /**
     * 魔术方法调用，支持请求实例接口额外支持的方法
     *
     * @param  string $method 方法名
     * @param  array  $params 参数
     * @throws InvalidArgumentException
     * @return mixed
     */
    public function __call($method, array $params)
    {
        if (is_callable([$this->service(), $method])) {
            return call_user_func_array([$this->service(), $method], $params);
        }

        throw new InvalidArgumentException("Request facade method not found => " . $method);
    }

    /**
     * 魔术属性调用，支持请求实例接口额外支持的属性
     *
     * @param string $name  属性名
     * @return mixed
     */
    public function __get(string $name)
    {
        if (property_exists($this->service(), $name)) {
            return $this->service()->$name;
        }

        throw new InvalidArgumentException("Request facade property not found => " . $name);
    }
}
