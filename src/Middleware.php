<?php

namespace mon\http;

use RuntimeException;
use mon\util\Instance;

/**
 * 全局中间件
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Middleware
{
    use Instance;

    /**
     * 全局中间件模块名
     *
     * @var string
     */
    protected $global_app = '';

    /**
     * 中间件
     *
     * @var array
     */
    protected $middlewares = [];

    /**
     * 私有化构造方法
     */
    protected function __construct()
    {
    }

    /**
     * 设置默认全局中间件模块名
     *
     * @param string $app 模块名
     * @return Middleware
     */
    public function setGlobalApp(string $app): Middleware
    {
        $this->global_app = $app;
        return $this;
    }

    /**
     * 获取默认全局中间件模块名
     *
     * @return string
     */
    public function getGlobalApp(): string
    {
        return $this->global_app;
    }

    /**
     * 注册全局中间件
     *
     * @param array $middlewares
     * @return Middleware
     */
    public function load(array $middlewares): Middleware
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
                $this->middlewares[$app][] = [App::instance()->container()->get($class), 'process'];
            }
        }

        return $this;
    }

    /**
     * 设置模块中间
     *
     * @param string $app   模块名
     * @param array $middlewares    中间件列表
     * @return Middleware
     */
    public function set(string $app, array $middlewares): Middleware
    {
        foreach ($middlewares as $class) {
            if (!method_exists($class, 'process')) {
                throw new RuntimeException("middleware {$class}::process not exsits");
            }
            // 生成实例进行存储
            $this->middlewares[$app][] = [App::instance()->container()->get($class), 'process'];
        }

        return $this;
    }

    /**
     * 获取中间件
     *
     * @param string $app 模块名
     * @param boolean $with_global  是否使用全局中间件
     * @return array
     */
    public function get(string $app, bool $with_global = true): array
    {
        $global_middleware = $with_global && isset($this->middlewares[$this->global_app]) ? $this->middlewares[$this->global_app] : [];
        if ($app == $this->global_app) {
            return $global_middleware;
        }

        $app_middleware = $this->middlewares[$app] ?? [];
        return array_merge($global_middleware, $app_middleware);
    }

    /**
     * 是否判断对应模块的中间件
     *
     * @param string $app
     * @return boolean
     */
    public function has(string $app): bool
    {
        return isset($this->middlewares[$app]);
    }
}
