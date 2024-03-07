<?php

declare(strict_types=1);

namespace support\http\middleware;

use Closure;
use mon\http\Response;
use mon\http\interfaces\RequestInterface;
use mon\http\interfaces\MiddlewareInterface;

/**
 * 跨域请求中间件
 * 
 * 使用该中间件，需要给所有OPTIONS请求设置options路由
 * $route->options('[{path:.+}]', function ($path = '') {
 *       return '';
 * });
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class CorsMiddleware implements MiddlewareInterface
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
        // 如果是opitons请求则返回一个空的响应，否则继续向洋葱芯穿越，并得到一个响应
        $response = $request->method() == 'OPTIONS' ? new Response() : $next($request);

        // 给响应添加跨域相关的http头
        $response->withHeaders([
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Origin' => $request->header('origin', '*'),
            'Access-Control-Allow-Methods' => $request->header('access-control-request-method', '*'),
            'Access-Control-Allow-Headers' => $request->header('access-control-request-headers', '*'),
        ]);

        return $response;
    }
}
