<?php

declare(strict_types=1);

namespace mon\worker;

use Closure;
use Throwable;
use ErrorException;
use Workerman\Worker;
use mon\util\Instance;
use FastRoute\Dispatcher;
use Psr\Log\LoggerInterface;
use mon\worker\interfaces\Container;
use Workerman\Connection\TcpConnection;
use mon\worker\exception\JumpException;
use mon\worker\exception\RouteException;
use mon\worker\support\ErrorHandler;

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
     * 日志对象
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * 是否支持静态文件访问
     *
     * @var boolean
     */
    protected $support_static_files = true;

    /**
     * 支持静态访问文件的类型
     *
     * @var array
     */
    protected $support_file_type = [];

    /**
     * 静态文件目录
     *
     * @var string
     */
    protected $static_path = '';

    /**
     * 缓存的处理器
     *
     * @var array
     */
    protected $cacheHandle = [];

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
    public function init(Worker $worker, Container $container, LoggerInterface $logger, bool $debug = true): App
    {
        // 绑定变量
        $this->worker = $worker;
        $this->container = $container;
        $this->logger = $logger;
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
     * 获取应用名
     *
     * @return string
     */
    public function name(): string
    {
        return 'mon';
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
     * 获取日志服务实例
     *
     * @return LoggerInterface
     */
    public function logger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * 请求回调
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @return void
     */
    public function onMessage(TcpConnection $connection, Request $request)
    {
        if (!$this->init) {
            throw new ErrorException('Please init the app');
        }

        try {
            // 绑定对象容器
            $this->connection = $connection;
            $this->request = $request;
            // 请求路径
            $path = $request->path();
            // 请求方式
            $method = $request->method();
            // 验证请求路径安全
            if (strpos($path, '..') !== false || strpos($path, "\\") !== false || strpos($path, "\0") !== false) {
                return $this->send(new Response(404));
            }
            // 执行文件
            $this->runFile($path);

            // 执行路由
            $this->runRoute($method, $path);
        } catch (Throwable $e) {
            return $this->send($this->handlerException($e, $request));
        }
    }

    /**
     * 查找静态文件资源
     *
     * @param string $path
     * @return void
     */
    protected function runFile(string $path): void
    {
        // 是否开启静态文件支持
        if (!$this->support_static_files) {
            return;
        }
        // 修正请求路径
        if (preg_match('/%[0-9a-f]{2}/i', $path)) {
            $path = urldecode($path);
        }
        // 验证文件扩展名白名单
        if (!empty($this->support_file_type) && !in_array(pathinfo($path, PATHINFO_EXTENSION), $this->support_file_type)) {
            return;
        }
        // 判断文件是否存在
        $file = "{$this->static_path}/{$path}";
        if (!file_exists($file)) {
            return;
        }
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
                $handler = $this->getHandler($callback[1], $callback[2]);
                // 缓存处理器
                $key = $method . $path;
                $this->cacheHandle[$key] = $handler;
                // 返回响应类实例
                $this->send($handler($this->request()));
                return;

                // 405 Method Not Allowed  方法不允许
            case Dispatcher::METHOD_NOT_ALLOWED:
                // 允许调用的请求类型
                throw new RouteException("Route method is not found", 405);

                // 404 Not Found 没找到对应的方法
            case Dispatcher::NOT_FOUND:
                $default = Route::instance()->dispatch($method, '*');
                if ($default[0] === Dispatcher::FOUND) {
                    // 存在自定义的默认处理路由
                    $handler = $this->getHandler($default[1], $default[2]);
                    // 缓存处理器
                    // $key = $method . '*';
                    // $this->cacheHandle[$key] = $handler;
                    // 返回响应类实例
                    $this->send($handler($this->request()));
                    return;
                }
                throw new RouteException("Route is not found", 404);

                // 不存在路由定义
            default:
                throw new RouteException("Route is not found!", 404);
        }
    }

    /**
     * 获取处理器
     *
     * @param  array  $callback 路由回调
     * @param  array  $vars     路由参数
     * @return Closure
     */
    protected function getHandler(array $callback, array $vars = [], string $app = ''): Closure
    {
        // 整理参数注入
        $args = array_values($vars);
        // 获取回调中间件
        $middlewares = Middleware::instance()->get($app);
        foreach ($callback['middleware'] as $middleware) {
            $middlewares[] = [$this->container()->get($middleware), 'process'];
        }
        // 获取回调方法
        $call = $this->getCallback($callback['callback']);
        // 执行中间件回调控制器方法
        if ($middlewares) {
            $callbackFun = array_reduce(array_reverse($middlewares), function ($carry, $pipe) {
                return function ($request) use ($carry, $pipe) {
                    return $pipe($request, $carry);
                };
            }, function ($request) use ($call, $args) {
                // 执行回调
                try {
                    $result = $call($request, ...$args);
                } catch (Throwable $e) {
                    return $this->handlerException($e, $request);
                }
                return $this->response($result);
            });
        } else {
            $callbackFun = function ($request) use ($call, $args) {
                // 没有中间件，直接执行控制器
                try {
                    $result = $call($request, ...$args);
                } catch (Throwable $e) {
                    return $this->handlerException($e, $request);
                }
                return $this->response($result);
            };
        }

        return $callbackFun;
    }

    /**
     * 处理异常
     *
     * @param Throwable $e  异常错误
     * @param Request $request  当前操作请求类
     * @return Response
     */
    protected function handlerException(Throwable $e, Request $request): Response
    {
        // 路由跳转
        if ($e instanceof JumpException) {
            return $e->getResponse();
        }

        try {
            // 自定义异常处理
            $handler = ErrorHandler::class;
            $params = [];
            /** @var \mon\worker\interfaces\ExceptionHandler */
            $callback = $this->container()->make($handler, $params, true);
            $callback->report($e);
            $response = $callback->render($request, $e);
            $response->exception($e);
            return $response;
        } catch (Throwable $err) {
            // 抛出异常
            $response = new Response(500, [], $this->debug ? (string)$err : $err->getMessage());
            $response->exception($err);
            return $response;
        }
    }

    /**
     * 获取回调方法
     *
     * @param mixed $callback
     * @throws RouteException
     * @return mixed
     */
    protected function getCallback($callback)
    {
        if ($callback instanceof Closure) {
            return $callback;
        }
        // 字符串
        if (is_string($callback)) {
            // 分割字符串获取对象和方法
            $call = explode('@', $callback);
            if (isset($call[0]) && isset($call[1])) {
                return [$this->container()->get($call[0]), $call[1]];
            }
        }
        // 数组
        if (is_array($callback) && isset($callback[0]) && isset($callback[1])) {
            return [$this->container()->get($callback[0]), $callback[1]];
        }

        throw new RouteException('Callback is faild!', 500);
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
        $this->connection()->close($response);
        $this->clearAction();
    }

    /**
     * 请求响应
     *
     * @return void
     */
    protected function clearAction(): void
    {
        $this->request = null;
        $this->connection = null;
    }
}
