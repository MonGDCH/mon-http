<?php

declare(strict_types=1);

namespace support\http\middleware;

use Closure;
use mon\env\Config;
use mon\http\Response;
use mon\util\Container;
use mon\http\interfaces\RequestInterface;
use mon\http\interfaces\MiddlewareInterface;
use support\http\middleware\throttle\CounterFixed;
use support\http\middleware\throttle\ThrottleAbstract;

/**
 * 请求限流中间件
 * 
 * @package topthink/think-throttle
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class ThrottleMiddleware implements MiddlewareInterface
{
    /**
     * 默认配置参数
     *
     * @var array
     */
    public static $default_config = [
        'enable' => false,                          // 是否启用
        'cache_name' => null,                       // 缓存驱动
        'driver_name' => CounterFixed::class,       // 限流算法驱动
        'prefix' => 'throttle_',                    // 缓存键前缀，防止键与其他应用冲突
        'visit_method' => ['GET', 'HEAD'],          // 要被限制的请求类型
        'visit_rate' => '100/m',                    // 节流频率 null 表示不限制 eg: 10/m  20/h  300/d
        'visit_enable_show_rate_limit' => true,     // 在响应体中设置速率限制的头部信息
        'visit_fail_code' => 429,                   // 访问受限时返回的http状态码
        'visit_fail_text' => 'Too Many Requests',   // 访问受限时访问的文本信息
    ];

    /**
     * 时间换算
     *
     * @var array
     */
    public static $duration = [
        's' => 1,
        'm' => 60,
        'h' => 3600,
        'd' => 86400,
    ];

    /**
     * 配置参数
     *
     * @var array
     */
    protected $config = [];

    /**
     * 缓存对象
     *
     * @var \Psr\SimpleCache\CacheInterface
     */
    protected $cache;

    /**
     * 算法驱动实例
     *
     * @var ThrottleAbstract
     */
    protected $driver_class;

    /**
     * 下次合法请求还有多少秒
     *
     * @var integer
     */
    protected $wait_seconds = 0;

    /**
     * 当前时间戳
     *
     * @var integer
     */
    protected $now = 0;

    /**
     * 规定时间内允许的最大请求次数
     *
     * @var integer
     */
    protected $max_requests = 0;

    /**
     * 规定时间
     *
     * @var integer
     */
    protected $expire = 0;

    /**
     * 规定时间内还能请求的次数
     *
     * @var integer
     */
    protected $remaining = 0;

    /**
     * 构造方法
     */
    public function __construct()
    {
        $this->config = array_merge(static::$default_config, Config::instance()->get('http.throttle', []));
        $this->cache = Container::instance()->get($this->config['cache_name'], [Config::instance()->get('cache', [])]);
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

        $allow = $this->allowRequest($request);
        if (!$allow) {
            // 访问受限
            return $this->buildLimitException($this->wait_seconds, $request);
        }

        /** @var Response $response */
        $response = $next($request);
        if (200 <= $response->getStatusCode() && 300 > $response->getStatusCode() && $this->config['visit_enable_show_rate_limit']) {
            // 将速率限制 headers 添加到响应中
            $response->withHeaders($this->getRateLimitHeaders());
        }

        return $response;
    }

    /**
     * 请求是否允许
     *
     * @param RequestInterface $request
     * @return boolean
     */
    protected function allowRequest(RequestInterface $request): bool
    {
        // 若请求类型不在限制内
        if (!in_array($request->method(), $this->config['visit_method'])) {
            return true;
        }

        // 获取缓存key
        $key = $this->getCacheKey($request);
        [$max_requests, $duration] = $this->parseRate($this->config['visit_rate']);

        $micronow = microtime(true);
        $now = (int) $micronow;

        $this->driver_class = Container::instance()->get($this->config['driver_name']);
        if (!$this->driver_class instanceof ThrottleAbstract) {
            throw new \TypeError('The throttle driver must extends ' . ThrottleAbstract::class);
        }
        $allow = $this->driver_class->allowRequest($key, $micronow, $max_requests, $duration, $this->cache);

        if ($allow) {
            // 允许访问
            $this->now = $now;
            $this->expire = $duration;
            $this->max_requests = $max_requests;
            $this->remaining = $max_requests - $this->driver_class->getCurRequests();
            return true;
        }

        $this->wait_seconds = $this->driver_class->getWaitSeconds();
        return false;
    }

    /**
     * 生成缓存的 key
     *
     * @param RequestInterface $request
     * @return string
     */
    protected function getCacheKey(RequestInterface $request): string
    {
        // 按业务需求修改key值，默认以IP为key
        $key = $request->ip();
        return md5($this->config['prefix'] . $key . $this->config['driver_name']);
    }

    /**
     * 解析频率配置项
     *
     * @param string $rate
     * @return array
     */
    protected function parseRate($rate): array
    {
        [$num, $period] = explode("/", $rate);
        $max_requests = (int) $num;
        $duration = static::$duration[$period] ?? (int) $period;
        return [$max_requests, $duration];
    }

    /**
     * 构建 Response Exception
     *
     * @param int $wait_seconds
     * @param RequestInterface $request
     * @return Response
     */
    public function buildLimitException(int $wait_seconds, RequestInterface $request): Response
    {
        // 默认响应429错误信息，可结合实际业务修改响应结果集
        $response = new Response($this->config['visit_fail_code'], [], $this->config['visit_fail_text']);
        if ($this->config['visit_enable_show_rate_limit']) {
            $response->withHeaders(['Retry-After' => $wait_seconds]);
        }
        return $response;
    }

    /**
     * 获取速率限制头
     *
     * @return array
     */
    public function getRateLimitHeaders(): array
    {
        return [
            'X-Rate-Limit-Limit' => $this->max_requests,
            'X-Rate-Limit-Remaining' => $this->remaining < 0 ? 0 : $this->remaining,
            'X-Rate-Limit-Reset' => $this->now + $this->expire,
        ];
    }
}
