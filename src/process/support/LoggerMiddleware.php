<?php

declare(strict_types=1);

namespace support\http;

use Closure;
use mon\log\Logger;
use mon\http\Response;
use mon\http\interfaces\RequestInterface;
use mon\http\interfaces\MiddlewareInterface;

/**
 * 日志记录中间件
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class LoggerMiddleware implements MiddlewareInterface
{
    /**
     * 中间件实现接口
     *
     * @param RequestInterface $request  请求实例
     * @param Closure $next 执行下一个中间件回调方法
     * @return Response
     */
    public function process(RequestInterface $request, Closure $next): Response
    {
        // 请求IP
        $ip = $request->ip();
        // 请求方式
        $method = $request->method();
        // 请求路径
        $url = $request->uri();
        // 日志内容
        $log = "{$ip} {$method} {$url}";
        // 日志记录
        Logger::instance()->channel()->log('bootstrap', $log);
        // 执行响应
        $response = $next($request);
        // 记录日志
        Logger::instance()->channel()->log('end', '=====================================', ['save' => true]);
        return $response;
    }
}
