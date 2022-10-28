<?php

namespace mon\http\interfaces;

/**
 * 请求实例接口
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface RequestInterface
{
    /**
     * 获取控制器名称
     *
     * @return string
     */
    public function controller(): string;

    /**
     * 获取控制器回调方法名称
     *
     * @return string
     */
    public function action(): string;

    /**
     * 获取GET数据
     *
     * @param mixed  $name      参数键名
     * @param mixed  $default   默认值
     * @param boolean $filter   是否过滤参数
     * @return mixed
     */
    public function get($name = null, $default = null, bool $filter = true);

    /**
     * 获取POST数据
     *
     * @param mixed  $name      参数键名
     * @param mixed  $default   默认值
     * @param boolean $filter   是否过滤参数
     * @return mixed
     */
    public function post($name = null, $default = null, bool $filter = true);

    /**
     * 获取application/json参数
     *
     * @param mixed $name       参数键名
     * @param mixed $default    默认值
     * @param boolean $filter   是否过滤参数
     * @return mixed
     */
    public function json($name = null, $default = null, bool $filter = true);

    /**
     * 获取header信息
     *
     * @param mixed $name    参数键名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function header($name = null, $default = null);

    /**
     * 获取$_SERVER数据
     *
     * @param  mixed  $name    参数键名
     * @param  mixed  $default 默认值
     * @return mixed
     */
    public function server($name = null, $default = null);

    /**
     * 获取请求Session
     *
     * @return mixed
     */
    public function session();

    /**
     * 获取请求Cookie
     *
     * @return mixed
     */
    public function cookie();

    /**
     * 获取上传文件
     *
     * @param mixed $name 文件参数名
     * @return mixed
     */
    public function file($name = null);

    /**
     * 获取请求类型
     *
     * @return string
     */
    public function method(): string;

    /**
     * 获取请求host
     *
     * @return string
     */
    public function host(): string;

    /**
     * 获取请求pathinfo路径
     *
     * @return string
     */
    public function path(): string;

    /**
     * 获取请求URI
     *
     * @return string
     */
    public function uri(): string;

    /**
     * 获取当前请求的域名
     *
     * @return string
     */
    public function url(): string;

    /**
     * 请求完整URL
     *
     * @return string
     */
    public function fullUrl(): string;

    /**
     * 获取真实IP
     *
     * @return string
     */
    public function ip(): string;

    /**
     * 获取HTTP协议版本号
     *
     * @return string
     */
    public function protocolVersion(): string;

    /**
     * 是否GET请求
     *
     * @return boolean
     */
    public function isGet(): bool;

    /**
     * 是否POST请求
     *
     * @return boolean
     */
    public function isPost(): bool;

    /**
     * 是否PUT请求
     *
     * @return boolean
     */
    public function isPut(): bool;

    /**
     * 是否DELETE请求
     *
     * @return boolean
     */
    public function isDelete(): bool;

    /**
     * 是否PATCH请求
     *
     * @return boolean
     */
    public function isPatch(): bool;

    /**
     * 是否HEAD请求
     *
     * @return boolean
     */
    public function isHead(): bool;

    /**
     * 是否OPTIONS请求
     *
     * @return boolean
     */
    public function isOptions(): bool;

    /**
     * 是否Ajax
     *
     * @return boolean
     */
    public function isAjax(): bool;
}
