<?php

declare(strict_types=1);

namespace mon\http;

use Throwable;
use mon\util\File;
use ErrorException;
use mon\http\Context;
use mon\http\Request;
use mon\http\Session;
use mon\http\libs\App;
use mon\util\Container;
use FastRoute\Dispatcher;
use mon\http\interfaces\AppInterface;
use mon\http\fpm\Session as FpmSession;
use mon\http\fpm\Request as FpmRequest;
use mon\http\interfaces\RequestInterface;

/**
 * FPM应用
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Fpm implements AppInterface
{
    use App;

    /**
     * 构造方法
     *
     * @param boolean $debug    是否为调试模式
     * @param string  $name     应用名称，也是中间件名
     */
    public function __construct(bool $debug = true, string $name = '__fpm__')
    {
        // 绑定应用驱动
        $this->debug = $debug;
        $this->app_name = $name;

        $this->request_class = Request::class;
        Context::set($this->request_class, new Request(Container::instance()->get(FpmRequest::class)));

        // 定义标志常量
        defined('IN_FPM') || define('IN_FPM', true);

        // 错误
        set_error_handler([$this, 'appError']);
        // 异常
        set_exception_handler([$this, 'appException']);
        // 致命错误|结束运行
        register_shutdown_function([$this, 'fatalError']);
    }

    /**
     * 自定义请求类支持
     *
     * @param string $request_class 请求类名
     * @return Fpm
     */
    public function supportRequest(string $request_class): Fpm
    {
        // 绑定请求对象
        if (!is_subclass_of($request_class, RequestInterface::class)) {
            throw new ErrorException('The Request object must implement ' . RequestInterface::class);
        }

        Context::set($this->request_class, new Request(Container::instance()->get($request_class)));
        return $this;
    }

    /**
     * 开启Session支持
     *
     * @param array $config session配置信息
     * @return Fpm
     */
    public function supportSession(array $config = []): Fpm
    {
        FpmSession::register($config);
        return $this;
    }

    /**
     * 执行
     *
     * @return void
     */
    public function run(): void
    {
        try {
            $method = $this->request()->method();
            $path = $this->request()->path();
            // 注册session服务
            Session::instance()->service(new FpmSession());
            // 解析路由
            $handler = $this->route()->dispatch($method, $path);
            if ($handler[0] === Dispatcher::FOUND) {
                // 绑定路由请求参数
                $this->request()->params = $handler[2];
                // 获取路由回调处理器
                $callback = $this->getCallback($handler[1], $handler[2], $this->app_name);
                // 获取路由回调处理器信息
                $callbackInfo = $this->getCallbackInfo($handler[1]['callback']);
                $this->request()->controller = $callbackInfo['controller'];
                $this->request()->action = $callbackInfo['action'];
                // 响应输出
                $response = $callback($this->request());
                $this->send($response);
                return;
            }

            // 未发现路由
            $failback = $this->getFallback();
            $this->send($failback($this->request()));
            return;
        } catch (Throwable $e) {
            $this->send($this->handlerException($e, $this->request()));
            return;
        }
    }

    /**
     * 输出响应结果集
     *
     * @param Response|string|array $response   响应结果集
     * @return void
     */
    public function send($response): void
    {
        $response = $this->response($response);
        // 存在发送文件，则发送文件
        if ($response->file) {
            $this->sendFile($response);
            return;
        }
        // 输出响应头
        if (!headers_sent()) {
            // 发送状态码
            http_response_code($response->getStatusCode());
            // 发送头部信息
            foreach ($response->getHeaders() as $name => $val) {
                if (is_null($val)) {
                    header($name);
                } else {
                    header($name . ':' . $val);
                }
            }
        }
        // 输出内容
        echo $response->rawBody();
        // fastcgi提高页面响应
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    /**
     * 发送文件
     *
     * @param array $file
     * @return void
     */
    protected function sendFile(Response $response): void
    {
        // 文件路径
        $file = $response->file['file'];
        // 输出响应头
        if (!headers_sent()) {
            // 发送状态码
            http_response_code($response->getStatusCode());
            // 发送头部信息
            $headers = $response->getHeaders();
            if (!isset($headers['Content-Type'])) {
                $mimeType = File::instance()->getMimeType($file);
                $headers['Content-Type'] = $mimeType ?: 'application/octet-stream';
            }
            if (!isset($headers['Content-Disposition'])) {
                $headers['Content-Disposition'] = 'attachment; filename=' . File::instance()->getBaseName($file);
            }
            if (!isset($headers['Last-Modified'])) {
                if ($mtime = filemtime($file)) {
                    $headers['Last-Modified'] = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';
                }
            }
            // 输出响应头
            foreach ($headers as $name => $val) {
                if (is_null($val)) {
                    header($name);
                } else {
                    header($name . ':' . $val);
                }
            }
        }

        // 刷新缓冲区 
        ob_clean();
        flush();
        // 输出文件
        readfile($file);
    }

    /**
     * 应用错误
     *
     * @param  integer $errno   错误编号
     * @param  string  $errstr  详细错误信息
     * @param  string  $errfile 出错的文件
     * @param  integer $errline 出错行号
     * @return void
     */
    public function appError(int $errno, string $errstr, string $errfile = '', int $errline = 0): void
    {
        // 清除输出缓冲区
        ob_get_clean();
        $exception = new ErrorException($errstr, 0, $errno, $errfile, $errline);
        throw $exception;
    }

    /**
     * 应用异常
     *
     * @param Throwable $e 异常错误实例
     * @return void
     */
    public function appException(Throwable $e): void
    {
        // 清除输出缓冲区
        ob_get_clean();
        $this->exceptionHandler()->report($e, $this->request());
        $response = $this->exceptionHandler()->render($e, $this->request(), $this->debug());
        $this->send($response);
        exit;
    }

    /**
     * PHP结束运行
     *
     * @return void
     */
    public function fatalError(): void
    {
        $error = error_get_last() ?: null;
        if (!is_null($error) && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            // 应用错误
            $this->appError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }
}
