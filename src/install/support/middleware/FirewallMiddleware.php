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
     * IP黑名单列表
     *
     * @example array ['192.168.1.13', '123.23.23.44', '193.134.*.*']
     * @var array
     */
    protected $black_list = [];

    /**
     * IP白名单列表
     *
     * @var array
     */
    protected $white_list = [];

    /**
     * 构造方法
     */
    public function __construct()
    {
        $this->black_list = array_merge($this->black_list, Config::instance()->get('http.app.firewall.black', []));
        $this->white_list = array_merge($this->white_list, Config::instance()->get('http.app.firewall.white', []));
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
        // 获取用户IP
        $ip = $request->ip();
        // 校验是否在IP黑名单中
        if (!empty($this->black_list)) {
            $check = Tool::instance()->safe_ip($ip, $this->black_list);
            if ($check) {
                // 黑名单中，返回404
                return new Response(404);
            }
        }
        // 校验是否再IP白名单中
        if (!empty($this->white_list)) {
            $check = Tool::instance()->safe_ip($ip, $this->white_list);
            if (!$check) {
                // 不存在白名单中，返回404
                return new Response(404);
            }
        }

        return $next($request);
    }
}
