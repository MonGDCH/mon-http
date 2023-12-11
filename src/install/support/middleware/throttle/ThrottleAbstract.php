<?php

declare(strict_types=1);

namespace support\http\middleware\throttle;

/**
 * 限流算法基类
 * 
 * @package topthink/think-throttle
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
abstract class ThrottleAbstract
{
    /**
     * 当前已有的请求数
     *
     * @var integer
     */
    protected int $cur_requests = 0;

    /**
     * 距离下次合法请求还有多少秒
     *
     * @var integer
     */
    protected int $wait_seconds = 0;

    /**
     * 是否允许请求
     *
     * @param string $key 缓存键
     * @param float $micronow 当前时间戳,可含毫秒
     * @param integer $max_requests 允许最大请求数
     * @param integer $duration 限流时长
     * @param \Psr\SimpleCache\CacheInterface $cache 缓存对象
     * @return boolean
     */
    abstract public function allowRequest(string $key, float $micronow, int $max_requests, int $duration, $cache): bool;

    /**
     * 计算距离下次合法请求还有多少秒
     *
     * @return integer
     */
    public function getWaitSeconds(): int
    {
        return $this->wait_seconds;
    }

    /**
     * 当前已有的请求数
     *
     * @return integer
     */
    public function getCurRequests(): int
    {
        return $this->cur_requests;
    }
}
