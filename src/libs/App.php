<?php

declare(strict_types=1);

namespace mon\http\libs;

use Closure;
use Throwable;
use ErrorException;
use mon\http\Route;
use ReflectionClass;
use ReflectionMethod;
use mon\http\Context;
use mon\http\Response;
use mon\util\Container;
use ReflectionFunction;
use mon\http\Middleware;
use mon\http\ErrorHandler;
use InvalidArgumentException;
use ReflectionFunctionAbstract;
use mon\http\interfaces\AppInterface;
use mon\http\exception\RouteException;
use mon\http\interfaces\RequestInterface;
use mon\http\interfaces\ExceptionHandlerInterface;

/**
 * 应用驱动，公共trait
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
trait App
{
    /**
     * 版本号
     *
     * @var string
     */
    protected $version = '1.3.2';

    /**
     * 应用名
     *
     * @var string
     */
    protected $app_name = '';

    /**
     * HTTP请求响应的request类对象名
     *
     * @var string
     */
    protected $request_class = '';

    /**
     * 异常处理类对象名
     *
     * @var string
     */
    protected $error_class = '';

    /**
     * 调试模式
     *
     * @var boolean
     */
    protected $debug = true;

    /**
     * 每次回调重新实例化控制器
     *
     * @var boolean
     */
    protected $new_ctrl = true;

    /**
     * 实例化后的控制器缓存
     *
     * @var array
     */
    protected $ctrl_cache = [];

    /**
     * 异常错误处理对象
     *
     * @var ExceptionHandlerInterface
     */
    protected $exception_handler = null;

    /**
     * PHP内置常规类型
     *
     * @var array
     */
    protected $adapters = ['int', 'float', 'string', 'bool', 'array', 'object', 'mixed', 'resource'];

    /**
     * 获取版本号
     *
     * @return string
     */
    public function version(): string
    {
        return $this->version;
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
     * 获取请求实例
     *
     * @return RequestInterface
     */
    public function request(): RequestInterface
    {
        return Context::get($this->request_class);
    }

    /**
     * 获取路由实例
     *
     * @return Route
     */
    public function route(): Route
    {
        return Route::instance();
    }

    /**
     * 获取错误处理服务实例
     *
     * @return ExceptionHandlerInterface
     */
    public function exceptionHandler(): ExceptionHandlerInterface
    {
        if (!$this->exception_handler) {
            $class = $this->error_class ?: ErrorHandler::class;
            $this->exception_handler = Container::instance()->make($class);
        }

        return $this->exception_handler;
    }

    /**
     * 自定义错误处理类支持
     *
     * @param string $error_class 错误处理类名
     * @return static
     */
    public function supportError(string $error_class): AppInterface
    {
        // 绑定请求对象
        if (!is_subclass_of($error_class, ExceptionHandlerInterface::class)) {
            throw new ErrorException('The ErrorHandler object must implement ' . ExceptionHandlerInterface::class);
        }

        $this->error_class = $error_class;
        return $this;
    }

    /**
     * 获取回调处理器
     *
     * @param array $handler 路由回调
     * @param array $params  路由参数
     * @param string $app    全局中间件模块名
     * @return Closure
     */
    public function getCallback(array $handler, array $params = [], string $app = ''): Closure
    {
        // 获取回调中间件
        $middlewares = Middleware::get($app);
        foreach ($handler['middleware'] as $middleware) {
            $middlewares[] = [Container::instance()->get($middleware), 'process'];
        }
        // 获取回调方法
        $call = $this->getCall($handler['callback']);
        // 执行中间件回调控制器方法
        if ($middlewares) {
            $callback = array_reduce(array_reverse($middlewares), function ($carry, $pipe) {
                // 执行中间件
                return function ($request) use ($carry, $pipe) {
                    try {
                        return $pipe($request, $carry);
                    } catch (Throwable $e) {
                        return $this->handlerException($e, $request);
                    }
                };
            }, function ($request) use ($call, $params) {
                // 执行回调
                try {
                    $result = $call($request, $params);
                    return $this->response($result);
                } catch (Throwable $e) {
                    return $this->handlerException($e, $request);
                }
            });
        } else {
            $callback = function ($request) use ($call, $params) {
                // 没有中间件，直接执行控制器
                try {
                    $result = $call($request, $params);
                    return $this->response($result);
                } catch (Throwable $e) {
                    return $this->handlerException($e, $request);
                }
            };
        }

        return $callback;
    }

    /**
     * 获取错误回调
     *
     * @return \callable
     */
    public function getFallback()
    {
        return $this->route()->getErrorHandler() ?: function () {
            return new Response(404, [], '<html><head><title>404 Not Found</title></head><body><center><h1>404 Not Found</h1></center></body></html>');
        };
    }

    /**
     * 获取回调信息
     *
     * @param mixed $callback  控制器回调
     * @return array
     */
    public function getCallbackInfo($callback): array
    {
        // 数组
        if (is_array($callback) && isset($callback[0]) && isset($callback[1])) {
            return ['controller' => $callback[0], 'action' => $callback[1]];
        }
        // 字符串
        if (is_string($callback)) {
            // 分割字符串获取对象和方法
            $call = explode('@', $callback, 2);
            if (isset($call[0]) && isset($call[1])) {
                return ['controller' => $call[0], 'action' => $call[1]];
            }
        }

        return ['controller' => '', 'action' => ''];
    }

    /**
     * 处理异常响应
     *
     * @param Throwable $e  异常错误
     * @param RequestInterface $request  当前操作请求实例
     * @return Response
     */
    public function handlerException(Throwable $e, RequestInterface $request): Response
    {
        try {
            // 自定义异常处理
            $this->exceptionHandler()->report($e, $request);
            $response = $this->exceptionHandler()->render($e, $request, $this->debug());
            $response->exception($e);
            return $response;
        } catch (Throwable $err) {
            // 抛出异常
            $response = new Response(500, [], $this->debug() ? (string) $err : $err->getMessage());
            $response->exception($err);
            return $response;
        }
    }

    /**
     * 获取回调方法
     *
     * @param mixed $callback   路由回调
     * @throws RouteException
     * @return Closure
     */
    protected function getCall($callback): Closure
    {
        // 分割字符串获取对象和方法
        if (is_string($callback)) {
            $callback = explode('@', $callback, 2);
        }
        // 验证回调类型，获取回调反射
        $isClosure = false;
        if ($callback instanceof Closure) {
            $isClosure = true;
            $refreflection = new ReflectionFunction($callback);
        } elseif (is_array($callback) && isset($callback[0]) && isset($callback[1])) {
            $refreflection = new ReflectionMethod($callback[0], $callback[1]);
        } else {
            throw new RouteException('Route callback not callable');
        }

        return function ($request, $args) use ($callback, $isClosure, $refreflection) {
            // 获取回调参数
            $params = $this->getCallParams($request, $args, $refreflection);
            if (!$isClosure) {
                // 获取回调控制器
                $controller = $this->getCallController($callback[0], $request);
                $callback = [$controller, $callback[1]];
            }

            return $callback(...$params);
        };
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
        return new Response(200, [], (string)$response);
    }

    /**
     * 解析获取回调方法依赖参数
     *
     * @param RequestInterface $request  请求实例
     * @param array $params  路由注入参数
     * @param ReflectionFunctionAbstract $reflection  回调反射
     * @throws RouteException
     * @return array
     */
    protected function getCallParams(RequestInterface $request, array $params, ReflectionFunctionAbstract $reflection): array
    {
        // 获取路由参数
        $params = $this->getRouteParams($params);
        // 解析获取依赖参数
        $parameters = [];
        /** @var \ReflectionParameter $parameter */
        foreach ($reflection->getParameters() as $parameter) {
            // 参数名称
            $paramsName = $parameter->getName();
            if ($parameter->hasType()) {
                /** @var \ReflectionNamedType $class 变量类型 */
                $class = $parameter->getType();
                // 指定类型，获取类型名称注入参数
                $typeName = $class->getName();
                if (in_array($typeName, $this->adapters)) {
                    // 内置类型
                    if (isset($params[$paramsName])) {
                        // 从路由参数中取值
                        $parameters[] = $params[$paramsName];
                    } elseif ($parameter->isDefaultValueAvailable()) {
                        // 取默认值
                        $parameters[] = $parameter->getDefaultValue();
                    } else {
                        // 不存在，抛出异常
                        throw new RouteException("Route callback bind parameters '{$paramsName}' were not found");
                    }
                } else {
                    // 自定义对象类型
                    if ($typeName == $this->request_class) {
                        // 请求类
                        $parameters[] = $request;
                    } else {
                        // 其他类
                        $parameters[] = Container::instance()->make($typeName);
                    }
                }
            } else {
                // 未指定类型，从路由参数中取值，不存在则取默认值
                if (isset($params[$paramsName])) {
                    // 从路由参数中取值
                    $parameters[] = $params[$paramsName];
                } elseif ($parameter->isDefaultValueAvailable()) {
                    // 取默认值
                    $parameters[] = $parameter->getDefaultValue();
                } else {
                    // 不存在，抛出异常
                    throw new RouteException("Route callback bind parameters '{$paramsName}' were not found");
                }
            }
        }

        return $parameters;
    }

    /**
     * 获取回调控制器，实现兼容构造方法参数注入
     *
     * @param string $controller    控制器名称
     * @param RequestInterface $request 请求类实例
     * @return object
     */
    protected function getCallController(string $controller, RequestInterface $request): object
    {
        // 从缓存中获取对象实例
        if (!$this->new_ctrl && isset($this->ctrl_cache[$controller])) {
            return $this->ctrl_cache[$controller];
        }
        // 创建控制器对象实例
        $reflect = new ReflectionClass($controller);
        // 获取构造方法
        $constructor = $reflect->getConstructor();
        // 参数结果集
        $args = [];
        if ($constructor && $constructor->getNumberOfParameters() > 0) {
            // 获取类方法需要的参数
            $parameters = $constructor->getParameters();
            // 获取参数类型, 绑定参数
            /** @var \ReflectionParameter $param */
            foreach ($parameters as $param) {
                // 变量名
                $name = $param->getName();
                /** @var \ReflectionNamedType $class 变量类型 */
                $class = $param->getType();
                // 绑定参数
                if ($class && !in_array($class->getName(), $this->adapters)) {
                    // 对象类型，且不是PHP内置常规类型，获取对象实例注入
                    $className = $class->getName();
                    if ($className == $this->request_class) {
                        $args[] = $request;
                    } else {
                        $args[] = Container::instance()->make($className);
                    }
                } elseif ($param->isDefaultValueAvailable()) {
                    // 获取默认值
                    $args[] = $param->getDefaultValue();
                } else {
                    throw new InvalidArgumentException('bind parameters were not found![' . $name . ']', 500);
                }
            }
        }
        // 创建实例
        $ctrl = $reflect->newInstanceArgs($args);
        // 是否缓存控制器实例
        if (!$this->new_ctrl) {
            $this->ctrl_cache[$controller] = $ctrl;
            return $this->ctrl_cache[$controller];
        }

        return $ctrl;
    }

    /**
     * 获取路由注入参数
     *
     * @param array $values   路由传参
     * @return array
     */
    protected function getRouteParams(array $values): array
    {
        $params = [];
        foreach ($values as $key => $val) {
            if (!is_numeric($val)) {
                $params[$key] = $val;
                continue;
            }
            if (is_int($val - 0)) {
                // 整数
                $params[$key] = intval($val);
            } elseif (is_float($val - 0)) {
                // 浮点数
                $params[$key] = floatval($val);
            }
        }

        return $params;
    }
}
