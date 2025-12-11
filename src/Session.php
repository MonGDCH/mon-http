<?php

declare(strict_types=1);

namespace mon\http;

use InvalidArgumentException;
use mon\http\interfaces\SessionInterface;

/**
 * Session类门面实体，用于统一兼容处理wokerman和fpm环境
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Session implements SessionInterface
{
    /**
     * 单例实体
     *
     * @var Session
     */
    protected static $instance = null;

    /**
     * session实例
     *
     * @var SessionInterface
     */
    protected $service = null;

    /**
     * 私有化构造方法
     */
    protected function __construct() {}

    /**
     * 获取单例
     *
     * @param mixed $options 初始化参数
     * @return Session
     */
    public static function instance(): Session
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * 获取服务实例
     *
     * @return SessionInterface
     */
    public function service(SessionInterface $service = null): ?SessionInterface
    {
        if (!is_null($service)) {
            $this->service = $service;
        }

        return $this->service;
    }

    /**
     * 清除服务实例
     *
     * @return void
     */
    public function clearHandler()
    {
        $this->service = null;
    }

    /**
     * 设置session, 支持.二级设置
     *
     * @param string $key   键名
     * @param mixed $value  键值
     * @return SessionInterface
     */
    public function set(string $key, $value = null): SessionInterface
    {
        return $this->service()->set($key, $value);
    }

    /**
     * 获取session值，支持.无限级获取值
     *
     * @param string $key       键名
     * @param mixed  $default   默认值
     * @return mixed
     */
    public function get(string $name = '', $default = null)
    {
        return $this->service()->get($name, $default);
    }

    /**
     * 是否存在某个key，支持.无限级判断
     *
     * @param string $name  键名
     * @return boolean
     */
    public function has(string $name): bool
    {
        return $this->service()->has($name);
    }

    /**
     * 删除session
     *
     * @param  string $key 键名
     * @return void
     */
    public function delete(string $key)
    {
        $this->service()->delete($key);
    }

    /**
     * 清空Session
     *
     * @return void
     */
    public function clear()
    {
        $this->service()->clear();
    }

    /**
     * 魔术方法调用，支持Session实例接口额外支持的方法
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

        throw new InvalidArgumentException("Session facade method not found => " . $method);
    }

    /**
     * 魔术属性调用，支持Session实例接口额外支持的属性
     *
     * @param string $name  属性名
     * @return mixed
     */
    public function __get(string $name)
    {
        if (property_exists($this->service(), $name)) {
            return $this->service()->$name;
        }

        throw new InvalidArgumentException("Session facade property not found => " . $name);
    }
}
