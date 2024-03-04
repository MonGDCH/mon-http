<?php

declare(strict_types=1);

namespace support\http\middleware;

use Closure;
use mon\util\Tool;
use mon\env\Config;
use mon\http\Response;
use mon\http\interfaces\RequestInterface;
use mon\http\interfaces\MiddlewareInterface;

/**
 * 防火墙中间件(IP黑名单)
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class FirewallMiddleware implements MiddlewareInterface
{
    /**
     * 配置信息
     *
     * @var array
     */
    protected $config = [
        // 是否启用访问防火墙
        'enable'    => false,
        // IP黑名单，['192.168.1.13', '123.23.23.44', '193.134.*.*']
        'black'     => [],
        // IP白名单
        'white'     => []
    ];

    /**
     * 构造方法
     */
    public function __construct()
    {
        $this->config = array_merge($this->config, Config::instance()->get('http.firewall', []));
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
        // 是否启用防火墙
        if (!$this->config['enable']) {
            return $next($request);
        }

        // 获取用户IP
        $ip = $request->ip();
        // 校验是否在IP黑名单中
        if (!empty($this->config['black'])) {
            $check = Tool::instance()->checkSafeIP($this->config['black'], $ip);
            if ($check) {
                // 黑名单中，返回404
                return new Response(404);
            }
        }
        // 校验是否再IP白名单中
        if (!empty($this->config['white'])) {
            $check = Tool::instance()->checkSafeIP($this->config['white'], $ip);
            if (!$check) {
                // 不存在白名单中，返回404
                return new Response(404);
            }
        }

        return $next($request);
    }
}
