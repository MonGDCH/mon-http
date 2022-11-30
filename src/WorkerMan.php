<?php

declare(strict_types=1);

namespace mon\http;

use Throwable;
use ErrorException;
use Workerman\Worker;
use mon\http\Request;
use mon\http\libs\App;
use FastRoute\Dispatcher;
use Workerman\Protocols\Http;
use mon\http\Session as HttpSession;
use Workerman\Protocols\Http\Session;
use Workerman\Connection\TcpConnection;
use mon\http\interfaces\RequestInterface;
use mon\http\workerman\Session as WorkermanSession;
use mon\http\workerman\Request as WorkermanRequest;

/**
 * WorkerMan应用
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class WorkerMan
{
    use App;

    /**
     * 静态模块名
     *
     * @var string
     */
    protected $static_name = '__static__';

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
     * TCP链接对象
     *
     * @var TcpConnection
     */
    protected $connection;

    /**
     * 构造方法
     *
     * @param string  $request  HTTP请求响应的request类对象名
     * @param boolean $debug    是否为调试模式
     * @param boolean $newCtrl  每次回调重新实例化控制器
     * @param string  $name     应用名称，也是中间件名
     */
    public function __construct(bool $debug = true, bool $newCtrl = true, string $name = '__worker__')
    {
        // 绑定参数
        $this->debug = $debug;
        $this->new_ctrl = $newCtrl;
        $this->app_name = $name;

        $this->request_class = Request::class;
        Http::requestClass(WorkermanRequest::class);

        // 定义标志常量
        defined('IN_WORKERMAN') || define('IN_WORKERMAN', true);

        // 错误
        set_error_handler([$this, 'appError']);
        // 致命错误|结束运行
        register_shutdown_function([$this, 'fatalError'], time());
    }

    /**
     * 自定义请求类支持
     *
     * @param string $request_class 请求类名
     * @return WorkerMan
     */
    public function supportRequest(string $request_class): WorkerMan
    {
        // 绑定请求对象
        if (!is_subclass_of($request_class, RequestInterface::class)) {
            throw new ErrorException('The Request object must implement ' . RequestInterface::class);
        }

        // $this->request_class = $request_class;
        Http::requestClass($request_class);

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
    public function supportStaticFile(bool $supportSatic, string $staticPath, array $supportType = [], string $name = '__static__'): WorkerMan
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
    public function supportSession(string $handler, array $setting = [], array $config = []): WorkerMan
    {
        Session::handlerClass($handler, $setting);
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
            if (isset($config[$key]) && property_exists(Session::class, $name)) {
                Session::${$name} = $config[$key];
            }
        }

        return $this;
    }

    /**
     * 执行回调
     *
     * @param TcpConnection $connection 链接实例
     * @param RequestInterface $request 请求实例
     * @return void
     */
    public function run(TcpConnection $connection, RequestInterface $request)
    {
        try {
            // 绑定对象容器
            $request->connection = $connection;
            $this->request = new Request($request);
            $this->connection = $connection;
            HttpSession::instance()->service(new WorkermanSession($this->request()->session()));
            // 请求路径
            $path = $this->request->path();
            // 请求方式
            $method = $this->request->method();
            // 验证请求路径安全
            if (strpos($path, '..') !== false || strpos($path, "\\") !== false || strpos($path, "\0") !== false || strpos($path, '//') !== false || !$path) {
                $failback = $this->getFallback();
                return $this->send($connection, $this->request, $failback($this->request));
            }
            // 判断是否存在缓存处理器，执行缓存处理器
            $key = $method . $path;
            if (isset($this->cacheCallback[$key])) {
                [$callback, $this->request->controller, $this->request->action] = $this->cacheCallback[$key];
                return $this->send($connection, $this->request, $callback($this->request));
            }
            // 处理文件响应
            if ($this->handlerFile($connection, $this->request, $path, $key)) {
                return;
            }
            // 处理路由响应
            if ($this->handlerRoute($connection, $this->request, $method, $path, $key)) {
                return;
            }

            // 错误回调响应
            $failback = $this->getFallback();
            return $this->send($connection, $this->request, $failback($this->request));
        } catch (Throwable $e) {
            // 异常响应
            return $this->send($connection, $this->request, $this->handlerException($e, $this->request));
        }
    }

    /**
     * 处理静态文件资源响应
     *
     * @param TcpConnection $connection 链接实例
     * @param RequestInterface $request 请求实例
     * @param string $method            请求方式
     * @param string $path              请求路径
     * @param string $key               缓存回调名称
     * @return boolean
     */
    protected function handlerFile(TcpConnection $connection, RequestInterface $request, string $path, string $key): bool
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
        $callback = $this->getCallback(['callback' => function ($req) use ($file) {
            clearstatcache(true, $file);
            if (!is_file($file)) {
                $failback = $this->getFallback();
                return $failback($req);
            }

            return (new Response())->file($file, $req);
        }, 'middleware' => []], ['req' => $request], $this->static_name);
        // 缓存处理器
        $request->controller = '';
        $request->action = '';
        // 判断清除缓存
        if (count($this->cacheCallback) > $this->maxCacheCallback) {
            $this->clearCacheCallback();
        }
        // 缓存回调处理器
        $this->cacheCallback[$key] = [$callback, '', ''];
        // 执行回调
        $this->send($connection, $request, $callback($request));

        return true;
    }

    /**
     * 处理路由响应
     *
     * @param TcpConnection $connection 链接实例
     * @param RequestInterface $request 请求实例
     * @param string $method            请求方式
     * @param string $path              请求路径
     * @param string $key               缓存回调名称
     * @return boolean
     */
    protected function handlerRoute(TcpConnection $connection, RequestInterface $request, string $method, string $path, string $key): bool
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
            // 判断清除缓存
            if (count($this->cacheCallback) > $this->maxCacheCallback) {
                $this->clearCacheCallback();
            }
            // 缓存回调处理器
            $this->cacheCallback[$key] = [$callback, $callbackInfo['controller'], $callbackInfo['action']];
            // 返回响应类实例
            $this->send($connection, $request, $callback($request));

            return true;
        }

        return false;
    }

    /**
     * 发送响应内容
     *
     * @param TcpConnection $connection 链接实例
     * @param RequestInterface $request 请求实例
     * @param string|array|Response $response 响应对象
     * @return void
     */
    protected function send(TcpConnection $connection, RequestInterface $request, $response): void
    {
        $this->request = null;
        $this->connection = null;
        HttpSession::instance()->clearHandler();

        $response = $this->response($response);
        $keep_alive = $request->header('connection');
        if (($keep_alive === null && $request->protocolVersion() === '1.1') || $keep_alive === 'keep-alive' || $keep_alive === 'Keep-Alive') {
            $connection->send($response);
            return;
        }
        $connection->close($response);
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
     * 清除回调处理器缓存
     *
     * @return WorkerMan
     */
    public function clearCacheCallback(): WorkerMan
    {
        $this->cacheCallback = [];
        return $this;
    }

    /**
     * 应用错误
     *
     * @param  integer $level   错误等级
     * @param  string  $errstr  详细错误信息
     * @param  string  $errfile 出错的文件
     * @param  integer $errline 出错行号
     * @return void
     */
    public function appError(int $level, string $errstr, string $errfile = '', int $errline = 0): void
    {
        if (error_reporting() & $level) {
            throw new ErrorException($errstr, 0, $level, $errfile, $errline);
        }
    }

    /**
     * PHP结束运行
     *
     * @return void
     */
    public function fatalError(int $start_time): void
    {
        // 运行worker的情况下不要立即结束进程，防止进程变成僵尸进程
        if (Worker::getAllWorkers() && time() - $start_time <= 1) {
            sleep(1);
        }
    }
}
