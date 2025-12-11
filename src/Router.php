<?php

declare(strict_types=1);

namespace mon\http;

use InvalidArgumentException;

/**
 * 路由器
 * 
 * @see 记录路由路径和请求方式
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Router
{
    /**
     * 路由名称记录
     *
     * @var array
     */
    protected static $routers = [];

    /**
     * 路由路径
     *
     * @var string
     */
    protected $path;

    /**
     * 请求方式
     *
     * @var array
     */
    protected $method;

    /**
     * 构造函数
     *
     * @param string $path   路由路径
     * @param array $method  请求方式
     */
    public function __construct(string $path, array $method)
    {
        $this->path = $path;
        $this->method = $method;
    }

    /**
     * 获取路由路径
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * 获取请求方式
     *
     * @return array
     */
    public function getMethod(): array
    {
        return $this->method;
    }

    /**
     * 设置路由名称
     *
     * @param string $name
     * @throws InvalidArgumentException
     * @return void
     */
    public function name(string $name)
    {
        if (isset(self::$routers[$name])) {
            throw new InvalidArgumentException("Router name already exists => " . $name);
        }
        self::$routers[$name] = $this->path;
    }

    /**
     * 注册路由表
     *
     * @param array $routers  路由表
     * @return void
     */
    public static function registerRoutes(array $routers)
    {
        self::$routers = $routers;
    }

    /**
     * 获取路由表
     *
     * @return array
     */
    public static function getRouters(): array
    {
        return self::$routers;
    }

    /**
     * 获取路由路径
     *
     * @param string $name  路由名称
     * @throws InvalidArgumentException
     * @return string
     */
    public static function getRouter(string $name): string
    {
        if (!isset(self::$routers[$name])) {
            throw new InvalidArgumentException("Router name not found => " . $name);
        }

        return self::$routers[$name];
    }
}
