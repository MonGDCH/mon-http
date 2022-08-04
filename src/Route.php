<?php

declare(strict_types=1);

namespace mon\worker;

use Closure;
use mon\util\Instance;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\Dispatcher\GroupCountBased as Dispatcher;

/**
 * 路由封装
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Route
{
    use Instance;

    /**
     * fast-route路由容器
     *
     * @var RouteCollector
     */
    protected $collector;

    /**
     * fast-route路由调度
     *
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * 路由信息
     *
     * @var array
     */
    protected $data = [];

    /**
     * 路由组前缀
     *
     * @var string
     */
    protected $groupPrefix = '';

    /**
     * 回调命名空间前缀
     *
     * @var string
     */
    protected $prefix = '';

    /**
     * 中间件
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * 私有化构造方法
     */
    private function __construct()
    {
    }

    /**
     * 设置路由数据
     *
     * @param array $data 路由数据
     * @return Route
     */
    public function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 获取路由数据
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data ?: $this->collector()->getData();
    }

    /**
     * 获取fast-route路由容器
     *
     * @return RouteCollector
     */
    public function collector(): RouteCollector
    {
        if (is_null($this->collector)) {
            $this->collector = new RouteCollector(new Std, new GroupCountBased);
        }

        return $this->collector;
    }

    /**
     * 获取fast-route路由调度
     *
     * @return Dispatcher
     */
    public function dispatcher(): Dispatcher
    {
        if (is_null($this->dispatcher)) {
            $this->dispatcher = new Dispatcher($this->getData());
        }

        return $this->dispatcher;
    }

    /**
     * 注册GET路由
     *
     * @param  mixed  $pattern  请求模式
     * @param  mixed  $callback 路由回调
     * @return Route
     */
    public function get($pattern, $callback): Route
    {
        return $this->map(['GET'], $pattern, $callback);
    }

    /**
     * 注册POST路由
     *
     * @param  mixed  $pattern  请求模式
     * @param  mixed  $callback 路由回调
     * @return Route
     */
    public function post($pattern, $callback): Route
    {
        return $this->map(['POST'], $pattern, $callback);
    }

    /**
     * 注册PUT路由
     *
     * @param  mixed  $pattern  请求模式
     * @param  mixed  $callback 路由回调
     * @return Route
     */
    public function put($pattern, $callback): Route
    {
        return $this->map(['PUT'], $pattern, $callback);
    }

    /**
     * 注册PATCH路由
     *
     * @param  mixed  $pattern  请求模式
     * @param  mixed  $callback 路由回调
     * @return Route
     */
    public function patch($pattern, $callback): Route
    {
        return $this->map(['PATCH'], $pattern, $callback);
    }

    /**
     * 注册DELETE路由
     *
     * @param  mixed  $pattern  请求模式
     * @param  mixed  $callback 路由回调
     * @return Route
     */
    public function delete($pattern, $callback): Route
    {
        return $this->map(['DELETE'], $pattern, $callback);
    }

    /**
     * 注册OPTIONS路由
     *
     * @param  mixed  $pattern  请求模式
     * @param  mixed  $callback 路由回调
     * @return Route
     */
    public function options($pattern, $callback): Route
    {
        return $this->map(['OPTIONS'], $pattern, $callback);
    }

    /**
     * 注册任意请求方式的路由
     *
     * @param  mixed  $pattern  请求模式
     * @param  mixed  $callback 路由回调
     * @return Route
     */
    public function any($pattern, $callback): Route
    {
        return $this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $pattern, $callback);
    }

    /**
     * 注册组别路由
     *
     * @param  mixed  $pattern  路由前缀
     * @param  Closure $callback 路由回调
     * @return void
     */
    public function group($pattern, Closure $callback)
    {
        $groupPrefix = $this->groupPrefix;
        $prefix = $this->prefix;
        $middleware  = $this->middleware;

        $parse = $this->parsePattern($pattern);
        $this->groupPrefix .= $parse['path'];
        $this->prefix = $parse['namespace'];
        $this->middleware  = $parse['middleware'];

        call_user_func($callback, $this);

        $this->groupPrefix = $groupPrefix;
        $this->prefix = $prefix;
        $this->middleware  = $middleware;
    }

    /**
     * 注册路由方法
     *
     * @param  array $method   请求方式
     * @param  mixed $pattern  请求模式
     * @param  mixed $callback 路由回调
     * @return Route
     */
    public function map(array $method, $pattern, $callback): Route
    {
        $parse = $this->parsePattern($pattern);
        // 获取请求路径
        $path = $this->groupPrefix . $parse['path'];
        // 获取请求回调
        if (is_string($callback)) {
            $callback = (!empty($parse['namespace']) ? $parse['namespace'] : $this->prefix) . $callback;
        }
        // 所有值转大写
        $method = array_map('strtoupper', $method);

        $result = [
            'middleware'=> $parse['middleware'],
            'callback'  => $callback,
        ];
        // 注册fast-route路由表
        $this->collector()->addRoute($method, $path, $result);

        return $this;
    }

    /**
     * 解析请求模式
     *
     * @param  mixed $pattern 路由参数
     * @return array
     */
    protected function parsePattern($pattern): array
    {
        $res = [
            // 路由路径或者路由前缀
            'path'      => '',
            // 命名空间
            'namespace' => $this->prefix,
            // 中间件
            'middleware'=> $this->middleware,
        ];
        if (is_string($pattern)) {
            // 字符串，标示请求路径
            $res['path'] = $pattern;
        } elseif (is_array($pattern)) {
            // 数组，解析配置
            if (isset($pattern['path']) && !empty($pattern['path'])) {
                $res['path'] = $pattern['path'];
            }
            if (isset($pattern['namespace']) && !empty($pattern['namespace'])) {
                $res['namespace'] = $pattern['namespace'];
            }
            if (isset($pattern['middleware']) && !empty($pattern['middleware'])) {
                $res['middleware'] = array_merge($this->middleware, (array) $pattern['middleware']);
            }
        }

        return $res;
    }

    /**
     * 执行路由
     *
     * @param  string $method 请求类型
     * @param  string $path   请求路径
     * @return array
     */
    public function dispatch(string $method, string $path): array
    {
        return $this->dispatcher()->dispatch($method, $path);
    }
}
