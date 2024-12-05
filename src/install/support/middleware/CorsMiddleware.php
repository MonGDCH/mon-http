<?php

declare(strict_types=1);

namespace support\http\middleware;

use Closure;
use mon\env\Config;
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
     * 配置信息
     *
     * @var array
     */
    protected $config = [
        // 允许所有域名跨域
        'allow_all' => false,
        // 允许跨域的域名
        'domain'    => []
    ];

    /**
     * 构造方法
     */
    public function __construct()
    {
        $this->config = array_merge($this->config, Config::instance()->get('http.cors', []));
    }

    /**
     * 获取配置信息
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

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

        $origin = $request->header('origin', '');
        if ($this->config['allow_all'] || ($origin && in_array($origin, $this->config['domain']))) {
            // 如果是允许的域名，则给响应添加跨域相关的http头
            $response->withHeaders([
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Allow-Origin' => $origin,
                'Access-Control-Allow-Methods' => $request->header('access-control-request-method', '*'),
                'Access-Control-Allow-Headers' => $request->header('access-control-request-headers', '*'),
            ]);
        }

        return $response;
    }
}
