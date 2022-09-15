<?php

declare(strict_types=1);

namespace mon\http;

use Closure;
use Throwable;
use ErrorException;
use Workerman\Worker;
use mon\util\Instance;
use mon\util\Container;
use FastRoute\Dispatcher;
use Workerman\Protocols\Http;
use mon\http\exception\JumpException;
use mon\http\exception\RouteException;
use Workerman\Connection\TcpConnection;
use mon\http\interfaces\ExceptionHandler;
use Workerman\Protocols\Http\Session as SessionBase;

/**
 * 应用驱动
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class App
{
    use Instance;

    /**
     * 版本号
     * 
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * 应用名
     *
     * @var string
     */
    protected $app_name = '__app__';

    /**
     * 静态模块名
     *
     * @var string
     */
    protected $static_name = '__static__';

    /**
     * HTTP请求响应的request类对象名
     *
     * @var string
     */
    protected $request_class = Request::class;

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
     * 路由对象
     *
     * @var Route
     */
    protected $route;

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
     * 是否重新创建回调控制器
     *
     * @var boolean
     */
    protected $newController = true;

    /**
     * 最大缓存回调处理器数
     *
     * @var integer
     */
    protected $maxCacheCallback = 1024;

    /**
     * 缓存的回调处理器
     *
     * @var array
     */
    protected $cacheCallback = [];

    /**
     * 构造方法
     */
    public function __construct()
    {
        // 绑定对象
        $this->route = new Route;
        $this->container = Container::instance();
        Http::requestClass($this->request_class);
    }

    /**
     * 初始化
     *
     * @param Worker $worker            worker实例
     * @param ExceptionHandler $handler 异常处理实例
     * @param boolean $debug            是否为调试模式
     * @param string  $name             应用名称，也是中间件名
     * @return App
     */
    public function init(Worker $worker, ExceptionHandler $handler, bool $debug = true, string $name = '__app__'): App
    {
        // 绑定变量
        $this->worker = $worker;
        $this->exceptionHandler = $handler;
        $this->debug = $debug;
        $this->app_name = $name;

        $this->init = true;
        return $this;
    }

    /**
     * 回调扩展支持
     *
     * @param boolean $newController    是否每次重新new控制器类
     * @param string  $request          HTTP请求响应的request类对象名
     * @param integer $maxCacheCallback 最大缓存回调数，一般不需要修改
     * @return App
     */
    public function suppertCallback(bool $newController = true, string $request = Request::class, int $maxCacheCallback = 1024): App
    {
        $this->newController = $newController;
        $this->request_class = $request;
        $this->maxCacheCallback = $maxCacheCallback;

        Http::requestClass($request);

        return $this;
    }

    /**
     * 静态文件支持
     *
     * @param boolean $supportSatic 是否开启静态文件支持
     * @param string $staticPath    静态文件目录
     * @param array $supportType    支持的文件类型，空则表示所有
     * @param string $name          静态全局中间件名
     * @return App
     */
    public function supportStaticFile(bool $supportSatic, string $staticPath, array $supportType = [], string $name = '__static__'): App
    {
        $this->support_static_files = $supportSatic;
        $this->static_path = $staticPath;
        $this->support_file_type = $supportType;
        $this->static_name = $name;

        return $this;
    }

    /**
     * Session扩展支持
     *
     * @param string $handler   驱动引擎，支持workerman内置驱动、或自定义驱动，需要实现\Workerman\Protocols\Http\Session\SessionHandlerInterface接口
     * @param array $setting    驱动引擎构造方法传参
     * @param array $config     Session公共配置
     * @return App
     */
    public function supportSession(string $handler, array $setting = [], array $config = []): App
    {
        SessionBase::handlerClass($handler, $setting);
        $map = [
            // session名称，默认：PHPSID
            'session_name'          => 'name',
            // 自动更新时间，默认：false
            'auto_update_timestamp' => 'autoUpdateTimestamp',
            // cookie有效期，默认：1440
            'cookie_lifetime'       => 'cookieLifetime',
            // cookie路径，默认：/
            'cookie_path'           => 'cookiePath',
            // 同站点cookie，默认：''
            'same_site'             => 'sameSite',
            // cookie的domain，默认：''
            'domain'                => 'domain',
            // 是否仅适用https的cookie，默认：false
            'secure'                => 'secure',
            // session有效期，默认：1440
            'lifetime'              => 'lifetime',
            // 是否开启http_only，默认：true
            'http_only'             => 'httpOnly',
            // gc的概率，默认：[1, 1000]
            'gc_probability'        => 'gcProbability',
        ];
        foreach ($map as $key => $name) {
            if (isset($config[$key]) && property_exists(SessionBase::class, $name)) {
                SessionBase::${$name} = $config[$key];
            }
        }

        return $this;
    }

    /**
     * 绑定路由器
     *
     * @param Route $route 路由实例
     * @return App
     */
    public function bindRoute(Route $route): App
    {
        $this->route = $route;
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
    public function worker(): ?Worker
    {
        return $this->worker;
    }

    /**
     * 获取TCP链接实例
     *
     * @return TcpConnection
     */
    public function connection(): ?TcpConnection
    {
        return $this->connection;
    }

    /**
     * 获取请求实例
     *
     * @return Request
     */
    public function request(): ?Request
    {
        return $this->request;
    }

    /**
     * 获取错误处理服务实例
     *
     * @return ExceptionHandler
     */
    public function exceptionHandler(): ?ExceptionHandler
    {
        return $this->exceptionHandler;
    }

    /**
     * 获取路由实例
     *
     * @return Route
     */
    public function route(): Route
    {
        return $this->route;
    }

    /**
     * 清除回调处理器缓存
     *
     * @return App
     */
    public function clearCacheCallback(): App
    {
        $this->cacheCallback = [];
        return $this;
    }

    /**
     * 请求回调
     *
     * @param TcpConnection $connection 链接实例
     * @param Request $request          请求实例
     * @return void
     */
    public function onMessage(TcpConnection $connection, Request $request): void
    {
        if (!$this->init) {
            throw new ErrorException('Please init the app');
        }

        try {
            // 绑定对象容器
            $request->connection = $connection;
            $this->connection = $connection;
            $this->request = $request;
            Session::instance()->request($request);
            // 请求路径
            $path = $request->path();
            // 请求方式
            $method = $request->method();
            // 验证请求路径安全
            if (strpos($path, '..') !== false || strpos($path, "\\") !== false || strpos($path, "\0") !== false || strpos($path, '//') !== false || !$path) {
                $failback = $this->getFallback($method);
                $this->send($connection, $request, $failback($request));
                return;
            }
            // 判断是否存在缓存处理器，执行缓存处理器
            $key = $method . $path;
            if (isset($this->cacheCallback[$key])) {
                [$callback, $request->controller, $request->action] = $this->cacheCallback[$key];
                $this->send($connection, $request, $callback($request));
                return;
            }
            // 处理文件响应
            if ($this->handlerFile($connection, $request, $method, $path, $key)) {
                return;
            }
            // 处理路由响应
            if ($this->handlerRoute($connection, $request, $method, $path, $key)) {
                return;
            }

            // 错误回调响应
            $failback = $this->getFallback($method);
            $this->send($connection, $request, $failback($request));
        } catch (Throwable $e) {
            $this->send($connection, $request, $this->handlerException($e, $request));
        }
        return;
    }

    /**
     * 处理静态文件资源响应
     *
     * @param TcpConnection $connection 链接实例
     * @param Request $request          请求实例
     * @param string $method            请求方式
     * @param string $path              请求路径
     * @param string $key               缓存回调名称
     * @return boolean
     */
    protected function handlerFile(TcpConnection $connection, Request $request, string $method, string $path, string $key): bool
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
        $callback = $this->getCallback(['callback' => function ($req) use ($method, $file) {
            clearstatcache(true, $file);
            if (!is_file($file)) {
                $failback = $this->getFallback($method);
                return $failback($req);
            }
            return (new Response())->file($file);
        }, 'middleware' => []], [], $this->static_name);
        // 缓存处理器
        $request->controller = '';
        $request->action = '';
        $this->cacheCallback[$key] = [$callback, '', ''];
        // 执行回调
        $this->send($connection, $request, $callback($request));
        // 判断清除缓存
        if (count($this->cacheCallback) > $this->maxCacheCallback) {
            $this->clearCacheCallback();
        }
        return true;
    }

    /**
     * 处理路由响应
     *
     * @param TcpConnection $connection 链接实例
     * @param Request $request          请求实例
     * @param string $method            请求方式
     * @param string $path              请求路径
     * @param string $key               缓存回调名称
     * @return boolean
     */
    protected function handlerRoute(TcpConnection $connection, Request $request, string $method, string $path, string $key): bool
    {
        // 执行路由
        $handler = $this->route()->dispatch($method, $path);
        if ($handler[0] === Dispatcher::FOUND) {
            // 获取路由回调处理器
            $callback = $this->getCallback($handler[1], $handler[2], $this->app_name);
            // 获取路由回调处理器信息
            $callbackInfo = $this->getCallbackInfo($handler[1]['callback']);
            $request->controller = $callbackInfo['controller'];
            $request->action = $callbackInfo['action'];
            // 缓存回调处理器
            $this->cacheCallback[$key] = [$callback, $callbackInfo['controller'], $callbackInfo['action']];
            // 返回响应类实例
            $this->send($connection, $request, $callback($request));
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
     * @param Request $request  当前操作请求实例
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
            $this->exceptionHandler()->report($e, $request);
            $response = $this->exceptionHandler()->render($e, $request);
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
     * 发送响应内容
     *
     * @param TcpConnection $connection 链接实例
     * @param Request $request          请求实例
     * @param string|Response $response 响应对象
     * @return void
     */
    protected function send(TcpConnection $connection, Request $request, $response): void
    {
        $this->clearAction();
        $keep_alive = $request->header('connection');
        if (($keep_alive === null && $request->protocolVersion() === '1.1') || $keep_alive === 'keep-alive' || $keep_alive === 'Keep-Alive') {
            $connection->send($response);
            return;
        }
        $connection->close($response);
    }

    /**
     * 获取错误回调
     *
     * @param string $method 请求类型
     * @return Closure
     */
    protected function getFallback(string $method): Closure
    {
        $handler = $this->route()->dispatch($method, '*');
        if ($handler[0] === Dispatcher::FOUND) {
            return $this->getCallback($handler[1], $handler[2], $this->app_name);
        }

        return function () {
            return new Response(404, [], '<html><head><title>404 Not Found</title></head><body><center><h1>404 Not Found</h1></center></body></html>');
        };
    }

    /**
     * 获取回调处理器
     *
     * @param array $handler 路由回调
     * @param array $vars    路由参数
     * @param string $app    全局中间件模块名
     * @return Closure
     */
    protected function getCallback(array $handler, array $vars = [], string $app = '__app__'): Closure
    {
        // 整理参数注入
        $args = array_values($vars);
        // 获取回调中间件
        $middlewares = Middleware::instance()->get($app);
        foreach ($handler['middleware'] as $middleware) {
            $middlewares[] = [Container::instance()->get($middleware), 'process'];
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
                    $controller = $this->newController ? Container::instance()->make($call[0]) : Container::instance()->get($call[0]);
                    $handler = [$controller, $call[1]];
                    return $handler(...$args);
                };
            }
        }
        // 数组
        if (is_array($callback) && isset($callback[0]) && isset($callback[1])) {
            return function (...$args) use ($callback) {
                $controller = $this->newController ? Container::instance()->make($callback[0]) : Container::instance()->get($callback[0]);
                $handler = [$controller, $callback[1]];
                return $handler(...$args);
            };
        }

        throw new RouteException('Callback is faild!', 500);
    }

    /**
     * 获取回调信息
     *
     * @param mixed $callback
     * @return array
     */
    protected function getCallbackInfo($callback): array
    {
        // 字符串
        if (is_string($callback)) {
            // 分割字符串获取对象和方法
            $call = explode('@', $callback);
            if (isset($call[0]) && isset($call[1])) {
                return ['controller' => $call[0], 'action' => $call[1]];
            }
        }
        // 数组
        if (is_array($callback) && isset($callback[0]) && isset($callback[1])) {
            return ['controller' => $callback[0], 'action' => $callback[1]];
        }
        return ['controller' => '', 'action' => ''];
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
        Session::instance()->request(null);
    }
}
