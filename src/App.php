<?php

declare(strict_types=1);

namespace mon\worker;

use Closure;
use Throwable;
use ErrorException;
use Workerman\Worker;
use mon\util\Instance;
use FastRoute\Dispatcher;
use mon\worker\interfaces\Container;
use Workerman\Connection\TcpConnection;
use mon\worker\exception\JumpException;
use mon\worker\exception\RouteException;
use mon\worker\interfaces\ExceptionHandler;

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
     * 异常错误处理对象
     *
     * @var ExceptionHandler
     */
    protected $exceptionHandler;

    /**
     * 是否支持静态文件访问
     *
     * @var boolean
     */
    protected $support_static_files = false;

    /**
     * 静态文件目录
     *
     * @var string
     */
    protected $static_path = '';

    /**
     * 支持静态访问文件的类型
     *
     * @var array
     */
    protected $support_file_type = [];

    /**
     * 缓存的回调处理器
     *
     * @var array
     */
    protected $cacheCallback = [];

    /**
     * 最大缓存回调处理器数
     *
     * @var integer
     */
    protected $maxCacheCallback = 1024;

    /**
     * 是否重新创建回调控制器
     *
     * @var boolean
     */
    protected $newController = true;

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
    public function init(Worker $worker, Container $container, ExceptionHandler $handler, bool $debug = true, bool $newController = true, int $maxCacheCallback = 1024): App
    {
        // 绑定变量
        $this->worker = $worker;
        $this->container = $container;
        $this->exceptionHandler = $handler;
        $this->debug = $debug;
        $this->newController = $newController;
        $this->maxCacheCallback = $maxCacheCallback;

        $this->init = true;
        return $this;
    }

    /**
     * 开启关闭静态文件支持
     *
     * @param boolean $supportSatic 是否开启静态文件支持
     * @param string $staticPath    静态文件目录
     * @param array $supportType    支持的文件类型，空则表示所有
     * @return App
     */
    public function supportStaticFile(bool $supportSatic, string $staticPath, array $supportType = []): App
    {
        $this->support_static_files = $supportSatic;
        $this->static_path = $staticPath;
        $this->support_file_type = $supportType;
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
     * 获取错误处理服务实例
     *
     * @return ExceptionHandler
     */
    public function exceptionHandler(): ExceptionHandler
    {
        return $this->exceptionHandler;
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
            // 请求路径
            $path = $request->path();
            // 请求方式
            $method = $request->method();
            // 验证请求路径安全
            if (strpos($path, '..') !== false || strpos($path, "\\") !== false || strpos($path, "\0") !== false) {
                $failback = $this->getFallback($method);
                $this->send($failback($request));
                return;
            }
            // 判断是否存在缓存处理器，执行缓存处理器
            $key = $method . $path;
            if (isset($this->cacheCallback[$key])) {
                $callback = $this->cacheCallback[$key];
                $this->send($callback($request));
                return;
            }
            // 处理文件响应
            if ($this->handlerFile($path, $key)) {
                return;
            }
            // 处理路由响应
            if ($this->handlerRoute($method, $path, $key)) {
                return;
            }

            // 错误回调响应
            $failback = $this->getFallback($method);
            $this->send($failback($request));
        } catch (Throwable $e) {
            $this->send($this->handlerException($e, $request));
        }
        return;
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
     * 清除回调处理器缓存
     *
     * @return void
     */
    public function clearCacheCallback(): void
    {
        $this->cacheCallback = [];
    }

    /**
     * 处理静态文件资源响应
     *
     * @param string $path
     * @return boolean
     */
    protected function handlerFile(string $path, string $key): bool
    {
        // 是否开启静态文件支持
        if (!$this->support_static_files || empty($this->static_path)) {
            return false;
        }
        // 修正请求路径
        if (preg_match('/%[0-9a-f]{2}/i', $path)) {
            $path = urldecode($path);
        }
        // 验证文件扩展名白名单
        if (!empty($this->support_file_type) && !in_array(pathinfo($path, PATHINFO_EXTENSION), $this->support_file_type)) {
            return false;
        }
        // 判断文件是否存在
        $file = "{$this->static_path}/{$path}";
        if (!is_file($file)) {
            return false;
        }

        // 生成处理器
        $callback = $this->getCallback(['callback' => function ($request) use ($file) {
            clearstatcache(true, $file);
            if (!is_file($file)) {
                $failback = $this->getFallback();
                return $failback($request);
            }
            return (new Response())->file($file);
        }, 'middleware' => []], [], '__static__');
        $this->cacheCallback[$key] = $callback;
        $this->send($callback($this->request()));
        // 判断清除缓存
        if (count($this->cacheCallback) > $this->maxCacheCallback) {
            $this->clearCacheCallback();
        }
        return true;
    }

    /**
     * 处理路由响应
     *
     * @param string $method    请求方式
     * @param string $path      请求路径
     * @param string $key       缓存键名
     * @throws RouteException
     * @return boolean
     */
    protected function handlerRoute(string $method, string $path, string $key): bool
    {
        // 执行路由
        $handler = Route::instance()->dispatch($method, $path);
        if ($handler[0] === Dispatcher::FOUND) {
            // 执行路由响应
            $callback = $this->getCallback($handler[1], $handler[2]);
            // 缓存回调处理器
            $this->cacheCallback[$key] = $callback;
            // 返回响应类实例
            $this->send($callback($this->request()));
            // 判断清除缓存
            if (count($this->cacheCallback) > $this->maxCacheCallback) {
                $this->clearCacheCallback();
            }
            return true;
        }

        return false;
    }

    /**
     * 处理异常响应
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
            $this->exceptionHandler()->report($e);
            $response = $this->exceptionHandler()->render($request, $e);
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
     * 获取错误回调
     *
     * @param string $method 请求类型
     * @return Closure
     */
    protected function getFallback(string $method = ''): Closure
    {
        $method = $method ?: $this->request()->method();
        $handler = Route::instance()->dispatch($method, '*');
        if ($handler[0] === Dispatcher::FOUND) {
            return $this->getCallback($handler[1], $handler[2]);
        }

        return function () {
            return new Response(404, [], '<html><head><title>404 Not Found</title></head><body><center><h1>404 Not Found</h1></center></body></html>');
        };
    }

    /**
     * 获取回调处理器
     *
     * @param  array  $handler 路由回调
     * @param  array  $vars     路由参数
     * @return Closure
     */
    protected function getCallback(array $handler, array $vars = [], string $app = ''): Closure
    {
        // 整理参数注入
        $args = array_values($vars);
        // 获取回调中间件
        $middlewares = Middleware::instance()->get($app);
        foreach ($handler['middleware'] as $middleware) {
            $middlewares[] = [$this->container()->get($middleware), 'process'];
        }
        // 获取回调方法
        $call = $this->getCall($handler['callback']);
        // 执行中间件回调控制器方法
        if ($middlewares) {
            $callback = array_reduce(array_reverse($middlewares), function ($carry, $pipe) {
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
            $callback = function ($request) use ($call, $args) {
                // 没有中间件，直接执行控制器
                try {
                    $result = $call($request, ...$args);
                } catch (Throwable $e) {
                    return $this->handlerException($e, $request);
                }
                return $this->response($result);
            };
        }

        return $callback;
    }

    /**
     * 获取回调方法
     *
     * @param mixed $callback
     * @throws RouteException
     * @return Closure
     */
    protected function getCall($callback): Closure
    {
        if ($callback instanceof Closure) {
            return $callback;
        }
        // 字符串
        if (is_string($callback)) {
            // 分割字符串获取对象和方法
            $call = explode('@', $callback);
            if (isset($call[0]) && isset($call[1])) {
                return function (...$args) use ($call) {
                    $controller = $this->newController ? $this->container()->make($call[0]) :  $this->container()->get($call[0]);
                    $handler = [$controller, $call[1]];
                    return $handler(...$args);
                };
            }
        }
        // 数组
        if (is_array($callback) && isset($callback[0]) && isset($callback[1])) {
            return function (...$args) use ($callback) {
                $controller = $this->newController ? $this->container()->make($callback[0]) :  $this->container()->get($callback[0]);
                $handler = [$controller, $callback[1]];
                return $handler(...$args);
            };
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
     * 清除响应
     *
     * @return void
     */
    protected function clearAction(): void
    {
        $this->request = null;
        $this->connection = null;
    }
}
