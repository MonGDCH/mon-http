<?php

declare(strict_types=1);

namespace mon\worker;

use Throwable;
use ErrorException;
use Workerman\Worker;
use mon\util\Instance;
use mon\worker\libs\Log;
use FastRoute\Dispatcher;
use mon\worker\interfaces\Container;
use Workerman\Connection\TcpConnection;
use mon\worker\exception\JumpException;
use mon\worker\exception\RouteException;

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
     * 获取运行模式
     *
     * @return boolean
     */
    public function debug(): bool
    {
        return $this->debug;
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
            // 绑定对象容器
            $this->connection = $connection;
            $this->request = $request;
            $this->container()->set(Request::class, $this->request);
            $this->container()->set(TcpConnection::class, $this->connection);
            // 请求路径
            $path = $request->path();
            // 请求方式
            $method = $request->method();
            // 验证请求路径
            if ($this->unsafeUri($path)) {
                return;
            }
            // 执行路由
            $this->runRoute($method, $path);
        } catch (Throwable $e) {
            $this->send($this->handlerException($e));
        }
        return;
    }

    /**
     * 验证请求安全
     *
     * @param string $path  请求路径
     * @return boolean
     */
    protected function unsafeUri(string $path): bool
    {
        if (strpos($path, '/../') !== false || strpos($path, "\\") !== false || strpos($path, "\0") !== false) {
            $this->send(new Response(404, [], ''));
            return true;
        }
        return false;
    }

    /**
     * 执行路由
     *
     * @param string $method    请求方式
     * @param string $path      请求路径
     * @throws RouteException
     * @return void
     */
    protected function runRoute(string $method, string $path): void
    {
        // 执行路由
        $callback = Route::instance()->dispatch($method, $path);
        switch ($callback[0]) {
                // 200 匹配请求
            case Dispatcher::FOUND:
                // 执行路由响应
                $response = $this->handler($callback[1], $callback[2]);
                // 返回响应类实例
                $this->send($response);
                return;

                // 405 Method Not Allowed  方法不允许
            case Dispatcher::METHOD_NOT_ALLOWED:
                // 允许调用的请求类型
                throw (new RouteException("Route method is not found", 405));

                // 404 Not Found 没找到对应的方法
            case Dispatcher::NOT_FOUND:
                $default = Route::instance()->dispatch($method, '*');
                if ($default[0] === Dispatcher::FOUND) {
                    // 存在自定义的默认处理路由
                    $response = $this->handler($default[1], $default[2]);
                    // 返回响应类实例
                    $this->send($response);
                    return;
                }
                throw new RouteException("Route is not found", 404);

                // 不存在路由定义
            default:
                throw new RouteException("Route is not found!", 404);
        }
    }

    /**
     * 执行路由
     *
     * @param  array  $callback 路由回调
     * @param  array  $vars     路由参数
     * @return Response
     */
    protected function handler(array $callback, array $vars = []): Response
    {
        // 获取回调中间件
        $middlewares = [];
        foreach ($callback['middleware'] as $middleware) {
            $middlewares[] = [$this->container()->get($middleware), 'handler'];
        }
        // 获取回调控制器
        $call = $callback['callback'];
        // 执行中间件回调控制器方法
        if ($middlewares) {
            $callbackFun = array_reduce(array_reverse($middlewares), function ($carry, $pipe) {
                return function ($request) use ($carry, $pipe) {
                    return $pipe($request, $carry);
                };
            }, function ($request) use ($call, $vars) {
                // 执行控制器
                $result = $this->container()->invoke($call, $vars);
                // var_dump($result);
                return $this->response($result);
            });
        } else {
            $callbackFun = function ($request) use ($call, $vars) {
                // 没有中间件，直接执行控制器
                $result = $this->container()->invoke($call, $vars);
                return $this->response($result);
            };
        }

        return $callbackFun($this->request());
    }

    /**
     * 处理异常
     *
     * @param Throwable $e 异常错误
     * @return Response
     */
    public function handlerException(Throwable $e): Response
    {
        // 路由跳转
        if ($e instanceof JumpException) {
            return $e->getResponse();
        }
        // 路由错误
        if ($e instanceof RouteException) {
            return new Response($e->getCode(), $e->getHeader(), $e->getMessage());
        }

        try {
            // 自定义异常处理
            $handler = Error::class;
            $params = [];
            /** @var \mon\worker\interfaces\ExceptionHandler */
            $callback = $this->container()->make($handler, $params, true);
            $callback->report($e);
            $response = $callback->render($this->request(), $e);
            $response->exception($e);
            return  $response;
        } catch (Throwable $err) {
            // 记录日志
            // $log = 'file: ' . $err->getFile() . ' line: ' . $err->getLine() . ' message: ' . $err->getMessage();
            // Log::instance()->error($log)->save();
            // 抛出异常
            $response = new Response(500, [], $this->debug ? (string)$err : $err->getMessage());
            $response->exception($err);
            return $response;
        }
    }

    /**
     * 生成输入的响应对象
     *
     * @param mixed $response 结果集
     * @return Response
     */
    protected function response($response): Response
    {
        if ($response instanceof Response) {
            return $response;
        } elseif (is_array($response)) {
            return new Response(200, ['Content-Type' => 'application/json'], json_encode($response, JSON_UNESCAPED_UNICODE));
        }
        return new Response(200, [], $response);
    }

    /**
     * 发送响应内容
     *
     * @param string|Response $response
     * @return void
     */
    public function send($response): void
    {
        $keep_alive = $this->request()->header('connection');
        if (($keep_alive === null && $this->request()->protocolVersion() === '1.1') || strtolower($keep_alive) === 'keep-alive') {
            $this->connection->send($response);
            $this->clearAction();
            return;
        }
        $this->connection->close($response);
        $this->clearAction();
    }

    /**
     * 请求响应
     *
     * @return void
     */
    protected function clearAction()
    {
        $this->request = null;
        $this->connection = null;
        $this->container()->set(Request::class, null);
        $this->container()->set(TcpConnection::class, null);
    }
}
