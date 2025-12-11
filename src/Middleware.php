<?php

namespace mon\http;

use RuntimeException;
use mon\util\Container;

/**
 * 全局中间件
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Middleware
{
    /**
     * 全局中间件模块名
     *
     * @var string
     */
    protected static $global_app = '';

    /**
     * 中间件
     *
     * @var array
     */
    protected static $middlewares = [];

    /**
     * 设置默认全局中间件模块名
     *
     * @param string $app 模块名
     * @return void
     */
    public static function setGlobalApp(string $app)
    {
        static::$global_app = $app;
    }

    /**
     * 获取默认全局中间件模块名
     *
     * @return string
     */
    public static function getGlobalApp(): string
    {
        return static::$global_app;
    }

    /**
     * 注册全局中间件
     *
     * @param array $middlewares
     * @return void
     */
    public static function load(array $middlewares)
    {
        foreach ($middlewares as $app => $list) {
            if (!is_array($list)) {
                throw new RuntimeException('Bad middleware config');
            }

            foreach ($list as $class) {
                if (!method_exists($class, 'process')) {
                    throw new RuntimeException("middleware {$class}::process not exsits");
                }
                // 生成实例进行存储
                static::$middlewares[$app][] = [Container::instance()->get($class), 'process'];
            }
        }
    }

    /**
     * 设置模块中间
     *
     * @param string $app   模块名
     * @param array $middlewares    中间件列表
     * @return void
     */
    public static function set(string $app, array $middlewares)
    {
        foreach ($middlewares as $class) {
            if (!method_exists($class, 'process')) {
                throw new RuntimeException("middleware {$class}::process not exsits");
            }
            // 生成实例进行存储
            static::$middlewares[$app][] = [Container::instance()->get($class), 'process'];
        }
    }

    /**
     * 获取中间件
     *
     * @param string $app 模块名
     * @param boolean $with_global  是否使用全局中间件
     * @return array
     */
    public static function get(string $app, bool $with_global = true): array
    {
        $global_middleware = $with_global && isset(static::$middlewares[static::$global_app]) ? static::$middlewares[static::$global_app] : [];
        if ($app == static::$global_app) {
            return $global_middleware;
        }

        $app_middleware = static::$middlewares[$app] ?? [];
        return array_merge($global_middleware, $app_middleware);
    }

    /**
     * 是否判断对应模块的中间件
     *
     * @param string $app
     * @return boolean
     */
    public static function has(string $app): bool
    {
        return isset(static::$middlewares[$app]);
    }
}
