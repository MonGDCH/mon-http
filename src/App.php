<?php

declare(strict_types=1);

namespace mon\worker;

use Closure;
use ErrorException;
use Throwable;
use Workerman\Worker;
use FastRoute\Dispatcher;
use mon\util\Instance;
use Workerman\Connection\TcpConnection;
use mon\worker\exception\RouteException;
use mon\worker\interfaces\Container;

/**
 * 应用实例
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class App
{
    use Instance;

    /**
     * 初始化标志
     *
     * @var boolean
     */
    protected $init = false;

    /**
     * 调试模式
     *
     * @var boolean
     */
    protected $debug = true;

    /**
     * Worker实例
     *
     * @var Worker
     */
    protected $worker;

    /**
     * TCP链接对象
     *
     * @var TcpConnection
     */
    protected $connection;

    /**
     * 请求对象
     *
     * @var Request
     */
    protected $request;

    /**
     * 容器对象
     *
     * @var Container
     */
    protected $container;

    /**
     * 私有初始化
     */
    protected function __construct()
    {
    }

    /**
     * 初始化
     *
     * @param Worker $worker
     * @param Container $container
     * @param boolean $debug
     * @return App
     */
    public function init(Worker $worker, Container $container, bool $debug = true): App
    {
        // 绑定变量
        $this->worker = $worker;
        $this->container = $container;
        $this->debug = $debug;

        $this->init = true;
        return $this;
    }

    /**
     * 请求回调
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @return void
     */
    public function onMessage(TcpConnection $connection, Request $request): void
    {
        if (!$this->init) {
            throw new ErrorException('Please init the app');
        }

        try {
            $this->connection = $connection;
            $this->request = $request;
            $path = $request->path();
            $method = $request->method();
            // 验证请求路径
            if ($this->unsafeUri($path)) {
                return;
            }
            // 执行路由
            $callback = Route::instance()->dispatch($method, $path);
            var_dump($callback);
            switch ($callback[0]) {
                    // 200 匹配请求
                case Dispatcher::FOUND:
                    // 执行路由响应
                    // $result = $this->handler($callback[1], $callback[2]);
                    // // 返回响应类实例
                    // return $this->response($result);
                    break;

                    // 405 Method Not Allowed  方法不允许
                case Dispatcher::METHOD_NOT_ALLOWED:
                    // 允许调用的请求类型
                    $allowedMethods = $callback[1];
                    throw (new RouteException("Route method is not found", 403))->set($allowedMethods);

                    // 404 Not Found 没找到对应的方法
                case Dispatcher::NOT_FOUND:
                    $default = Route::instance()->dispatch($method, '*');
                    if ($default[0] === Dispatcher::FOUND) {
                        // // 存在自定义的默认处理路由
                        // $result = $this->handler($default[1], $default[2]);
                        // // 返回响应类实例
                        // return $this->response($result);
                    }
                    throw new RouteException("Route is not found", 404);

                    // 不存在路由定义
                default:
                    throw new RouteException("Route is not found!", 404);
            }

            $this->send($request->url());
        } catch (Throwable $e) {
            $this->send($e->getMessage());
        }

        return;
    }

    /**
     * 获取woker实例
     *
     * @return Worker
     */
    public function worker(): Worker
    {
        return $this->worker;
    }

    /**
     * 获取TCP链接实例
     *
     * @return TcpConnection
     */
    public function connection(): TcpConnection
    {
        return $this->connection;
    }

    /**
     * 获取请求实例
     *
     * @return Request
     */
    public function request(): Request
    {
        return $this->request;
    }

    /**
     * 获取容器实例
     *
     * @return Container
     */
    public function container(): Container
    {
        return $this->container;
    }

    /**
     * 验证请求安全
     *
     * @param string $path
     * @return boolean
     */
    protected function unsafeUri(string $path): bool
    {
        if (strpos($path, '/../') !== false || strpos($path, "\\") !== false || strpos($path, "\0") !== false) {
            // $callback = static::getFallback();
            // $request->app = $request->controller = $request->action = '';
            // static::send($connection, $callback($request), $request);
            $this->send('404');
            return true;
        }
        return false;
    }

    /**
     * 发送响应内容
     *
     * @param mixed $response
     * @return void
     */
    protected function send($response): void
    {
        $keep_alive = $this->request->header('connection');
        if (($keep_alive === null && $this->request->protocolVersion() === '1.1') || strtolower($keep_alive) === 'keep-alive') {
            $this->connection->send($response);
            // return;
        }
        $this->connection->close($response);

        $this->connection = null;
        $this->request = null;
    }

    /**
     * 执行路由
     *
     * @param  mixed  $callback 路由回调标志
     * @param  array  $vars     路由参数
     * @return mixed
     */
    protected function handler($callback, array $vars = [])
    {
        // 获得处理函数
        $this->callback = $callback;
        // 获取请求参数
        $this->vars = $vars;
        // 获取回调中间件
        $this->befor = $this->callback['befor'];
        // 获取回调控制器
        $this->controller = $this->callback['callback'];
        // 获取回调后置件
        $this->after = $this->callback['after'];

        // 执行中间件
        if ($this->befor) {
            // 存在中间件，执行中间件，绑定参数：路由请求参数和App实例
            $result = $this->kernel($this->befor, $this->vars);
            if ($result === true) {
                $result = $this->callback();
            }
        } else {
            // 不存在中间件，执行控制器及后置件
            $result = $this->callback();
        }

        return $result;
    }

    /**
     * 执行中间件
     *
     * @param mixed $kernel         中间件
     * @param array|string $vars    参数
     * @return mixed
     */
    protected function kernel($kernel, $vars = [])
    {
        // 转为数组
        $kernel = !is_array($kernel) ? [$kernel] : $kernel;
        foreach ($kernel as $k => $v) {
            // 执行回调，不返回true，则结束执行，返回中间件的返回结果集
            $result = $this->exec($v, $vars);
            if ($result !== true) {
                return $result;
            }
        }

        return true;
    }

    /**
     * 执行业务回调
     *
     * @return mixed
     */
    protected function callback()
    {
        // 执行控制器
        $this->result = $this->container->invoke($this->controller, $this->vars);
        // 执行后置件
        if ($this->after) {
            $this->result = $this->kernel($this->after, $this->result);
        }

        return $this->result;
    }

    /**
     * 执行回调
     *
     * @param mixed  $kernel        回调对象
     * @param array|string $vars    参数
     * @return mixed
     */
    protected function exec($kernel, $vars = [])
    {
        if (is_string($kernel) || (is_object($kernel) && !($kernel instanceof Closure))) {
            $kernel = [$this->container->get($kernel), 'handler'];
        }

        return $this->container->invoke($kernel, [$this, $vars]);
    }
}
